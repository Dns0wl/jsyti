<?php
/**
 * Cron handler for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWIP_Cron {

    const CRON_HOOK = 'hwip_monthly_backfill';

    /**
     * Plugin instance.
     *
     * @var HWIP_Plugin
     */
    protected $plugin;

    /**
     * Constructor.
     *
     * @param HWIP_Plugin $plugin Plugin.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;

        add_filter( 'cron_schedules', array( __CLASS__, 'register_schedule' ) );
        add_action( self::CRON_HOOK, array( $this, 'process_backfill' ) );
        add_action( 'init', array( $this, 'maybe_schedule_event' ) );
    }

    /**
     * Register quarterly schedule.
     *
     * @param array $schedules Cron schedules.
     *
     * @return array
     */
    public static function register_schedule( $schedules ) {
        if ( ! isset( $schedules['hwip_monthly'] ) ) {
            $schedules['hwip_monthly'] = array(
                'interval' => 60 * 60 * 24 * 30,
                'display'  => __( 'Once every month', 'hw-invoice-pdf' ),
            );
        }

        return $schedules;
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        add_filter( 'cron_schedules', array( __CLASS__, 'register_schedule' ) );
        self::clear_hook( 'hwip_quarterly_backfill' );
        self::clear_hook( self::CRON_HOOK );

        self::schedule_event();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        self::clear_hook( self::CRON_HOOK );
    }

    /**
     * Ensure the cron event is scheduled.
     */
    public function maybe_schedule_event() {
        self::schedule_event();
    }

    /**
     * Schedule the cron event if missing.
     */
    protected static function schedule_event() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'hwip_monthly', self::CRON_HOOK );
        }
    }

    /**
     * Remove all scheduled events for a hook.
     *
     * @param string $hook Cron hook name.
     */
    protected static function clear_hook( $hook ) {
        if ( empty( $hook ) ) {
            return;
        }

        $timestamp = wp_next_scheduled( $hook );

        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
            $timestamp = wp_next_scheduled( $hook );
        }
    }

    /**
     * Process backfill batch.
     *
     * @param int $limit Items per batch.
     *
     * @return int Processed orders count.
     */
    public function process_backfill( $limit = 75 ) {
        if ( ! function_exists( 'wc_get_orders' ) || ! $this->plugin || ! $this->plugin->invoice ) {
            return 0;
        }

        $statuses = array_map(
            function( $status ) {
                return str_replace( 'wc-', '', $status );
            },
            array_keys( wc_get_order_statuses() )
        );

        $orders = wc_get_orders(
            array(
                'limit'      => absint( $limit ),
                'orderby'    => 'date',
                'order'      => 'DESC',
                'status'     => $statuses,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_hw_invoice_number',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_hw_invoice_number',
                        'value'   => '',
                        'compare' => '=',
                    ),
                ),
            )
        );

        $processed = 0;

        foreach ( $orders as $order ) {
            if ( ! $order instanceof WC_Order ) {
                continue;
            }

            $this->plugin->invoice->build_invoice_id( $order );
            $processed++;
        }

        $status = array(
            'last_run' => current_time( 'mysql' ),
            'processed' => $processed,
        );

        update_option( HWIP_Plugin::BACKFILL_OPTION, $status );

        return $processed;
    }

    /**
     * Manually trigger backfill.
     *
     * @param int $limit Batch size.
     *
     * @return int
     */
    public function run_manual_backfill( $limit = 75 ) {
        return $this->process_backfill( $limit );
    }

    /**
     * Get status data.
     *
     * @return array
     */
    public function get_status() {
        $defaults = array(
            'last_run' => '',
            'processed' => 0,
        );

        $status = get_option( HWIP_Plugin::BACKFILL_OPTION, array() );

        return wp_parse_args( $status, $defaults );
    }
}
