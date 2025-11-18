<?php
/**
 * Core plugin loader for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWIP_Plugin {

    const OPTION_KEY        = 'hwip_settings';
    const BACKFILL_OPTION   = 'hwip_backfill_status';

    /**
     * Singleton instance.
     *
     * @var HWIP_Plugin
     */
    private static $instance;

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Invoice helper.
     *
     * @var HWIP_Invoice
     */
    public $invoice;

    /**
     * PDF generator.
     *
     * @var HWIP_PDF_Generator
     */
    public $pdf_generator;

    /**
     * Admin handler.
     *
     * @var HWIP_Admin
     */
    public $admin;

    /**
     * Cron handler.
     *
     * @var HWIP_Cron
     */
    public $cron;

    /**
     * Whether plugin initialized.
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Get singleton instance.
     *
     * @return HWIP_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        HWIP_Cron::activate();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        HWIP_Cron::deactivate();
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'maybe_init' ), 20 );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'hw-invoice-pdf', false, dirname( plugin_basename( HWIP_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Initialize plugin when WooCommerce is active.
     */
    public function maybe_init() {
        if ( $this->initialized ) {
            return;
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
            return;
        }

        $this->invoice       = new HWIP_Invoice( $this );
        $this->pdf_generator = new HWIP_PDF_Generator( $this );
        $this->cron          = new HWIP_Cron( $this );

        add_action( 'woocommerce_new_order', array( $this->invoice, 'ensure_invoice_for_order' ), 20 );
        add_action( 'woocommerce_checkout_order_processed', array( $this->invoice, 'ensure_invoice_for_order' ), 20 );
        add_action( 'woocommerce_order_status_changed', array( $this->invoice, 'ensure_invoice_for_order' ), 20, 1 );

        if ( is_admin() ) {
            $this->admin = new HWIP_Admin( $this );
        }

        $this->initialized = true;
    }

    /**
     * Admin notice for missing WooCommerce.
     */
    public function woocommerce_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        echo '<div class="notice notice-error"><p>' . esc_html__( 'HW Invoice PDF requires WooCommerce to be installed and active.', 'hw-invoice-pdf' ) . '</p></div>';
    }

    /**
     * Get plugin settings.
     *
     * @return array
     */
    public function get_settings() {
        $settings = get_option( self::OPTION_KEY, array() );

        return hwip_parse_settings( $settings );
    }

    /**
     * Update plugin settings.
     *
     * @param array $settings Settings.
     */
    public function update_settings( $settings ) {
        update_option( self::OPTION_KEY, hwip_parse_settings( $settings ) );
    }

    /**
     * Get template path.
     *
     * @param string $template Template filename.
     *
     * @return string
     */
    public function get_template_path( $template ) {
        return trailingslashit( HWIP_PLUGIN_DIR . 'templates' ) . $template;
    }

    /**
     * Get asset URL.
     *
     * @param string $asset Asset relative path.
     *
     * @return string
     */
    public function get_asset_url( $asset ) {
        return HWIP_PLUGIN_URL . ltrim( $asset, '/' );
    }
}
