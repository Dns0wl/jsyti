<?php
/**
 * Admin logic for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWIP_Admin {

    const PAGE_SLUG = 'hw-invoice-pdf';

    /**
     * Plugin instance.
     *
     * @var HWIP_Plugin
     */
    protected $plugin;

    /**
     * Constructor.
     *
     * @param HWIP_Plugin $plugin Plugin instance.
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_hwip_save_settings', array( $this, 'handle_save_settings' ) );
        add_action( 'admin_post_hwip_invoice_pdf', array( $this, 'handle_invoice_pdf' ) );
        add_action( 'admin_post_hwip_invoice_regenerate_pdf', array( $this, 'handle_invoice_regenerate_pdf' ) );
        add_action( 'admin_post_hwip_invoice_preview', array( $this, 'handle_invoice_preview' ) );
        add_action( 'admin_post_hwip_run_backfill', array( $this, 'handle_manual_backfill' ) );
        add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_action' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'register_order_metabox' ) );
        add_action( 'admin_notices', array( $this, 'show_notices' ) );
    }

    /**
     * Register submenu under WooCommerce.
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'HW Invoice PDF', 'hw-invoice-pdf' ),
            __( 'HW Invoice PDF', 'hw-invoice-pdf' ),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current hook.
     */
    public function enqueue_assets( $hook ) {
        $settings_screen = 'woocommerce_page_' . self::PAGE_SLUG;

        if ( $settings_screen === $hook ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_media();
            wp_enqueue_style( 'hwip-admin', $this->plugin->get_asset_url( 'assets/css/admin.css' ), array(), $this->plugin->version );
            wp_enqueue_script( 'hwip-admin', $this->plugin->get_asset_url( 'assets/js/admin.js' ), array( 'jquery', 'wp-color-picker' ), $this->plugin->version, true );

            wp_localize_script(
                'hwip-admin',
                'HWIPAdmin',
                array(
                    'chooseLogo'    => __( 'Choose Logo', 'hw-invoice-pdf' ),
                    'chooseImage'   => __( 'Choose Image', 'hw-invoice-pdf' ),
                    'useImage'      => __( 'Use this image', 'hw-invoice-pdf' ),
                )
            );
        }

        $order_hook_match = 'edit-shop_order' === $hook || 0 === strpos( $hook, 'woocommerce_page_wc-orders' );

        if ( $order_hook_match ) {
            wp_enqueue_style( 'hwip-order-actions', $this->plugin->get_asset_url( 'assets/css/order-actions.css' ), array(), $this->plugin->version );
        }
    }

    /**
     * Render plugin settings page.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $settings = $this->plugin->get_settings();
        $status   = $this->plugin->cron ? $this->plugin->cron->get_status() : array();
        $logo_id  = absint( $settings['store_logo_id'] );
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $bg_id    = absint( $settings['background_image_id'] );
        $bg_url   = $bg_id ? wp_get_attachment_image_url( $bg_id, 'large' ) : '';
        ?>
        <div class="wrap hwip-wrap">
            <h1><?php esc_html_e( 'HW Invoice PDF', 'hw-invoice-pdf' ); ?></h1>
            <div class="hwip-grid">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hwip-card">
                    <h2><?php esc_html_e( 'General Settings', 'hw-invoice-pdf' ); ?></h2>
                    <?php wp_nonce_field( 'hwip_save_settings', 'hwip_settings_nonce' ); ?>
                    <input type="hidden" name="action" value="hwip_save_settings" />
                    <div class="hwip-field">
                        <label><?php esc_html_e( 'Store Logo', 'hw-invoice-pdf' ); ?></label>
                        <div class="hwip-media-control" data-target="hwip_store_logo_id">
                            <input type="hidden" id="hwip_store_logo_id" name="store_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                            <img src="<?php echo esc_url( $logo_url ); ?>" class="hwip-media-preview" <?php echo $logo_url ? '' : 'style="display:none;"'; ?> alt="" />
                            <div>
                                <button type="button" class="button hwip-upload-media" data-title="<?php esc_attr_e( 'Select Logo', 'hw-invoice-pdf' ); ?>"><?php esc_html_e( 'Choose Logo', 'hw-invoice-pdf' ); ?></button>
                                <button type="button" class="button hwip-remove-media"><?php esc_html_e( 'Remove', 'hw-invoice-pdf' ); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_store_name"><?php esc_html_e( 'Store Name', 'hw-invoice-pdf' ); ?></label>
                        <input type="text" id="hwip_store_name" name="store_name" value="<?php echo esc_attr( $settings['store_name'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_store_address"><?php esc_html_e( 'Store Address', 'hw-invoice-pdf' ); ?></label>
                        <textarea id="hwip_store_address" name="store_address" rows="4"><?php echo esc_textarea( $settings['store_address'] ); ?></textarea>
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_store_phone"><?php esc_html_e( 'Store Phone', 'hw-invoice-pdf' ); ?></label>
                        <input type="text" id="hwip_store_phone" name="store_phone" value="<?php echo esc_attr( $settings['store_phone'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_store_email"><?php esc_html_e( 'Store Email', 'hw-invoice-pdf' ); ?></label>
                        <input type="email" id="hwip_store_email" name="store_email" value="<?php echo esc_attr( $settings['store_email'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_store_website"><?php esc_html_e( 'Store Website URL', 'hw-invoice-pdf' ); ?></label>
                        <input type="url" id="hwip_store_website" name="store_website" value="<?php echo esc_attr( $settings['store_website'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_footer_text"><?php esc_html_e( 'Footer Text', 'hw-invoice-pdf' ); ?></label>
                        <textarea id="hwip_footer_text" name="footer_text" rows="4"><?php echo esc_textarea( $settings['footer_text'] ); ?></textarea>
                    </div>
                    <?php submit_button( __( 'Save Settings', 'hw-invoice-pdf' ) ); ?>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hwip-card">
                    <h2><?php esc_html_e( 'Design & Template', 'hw-invoice-pdf' ); ?></h2>
                    <?php wp_nonce_field( 'hwip_save_settings', 'hwip_settings_nonce' ); ?>
                    <input type="hidden" name="action" value="hwip_save_settings" />
                    <div class="hwip-field">
                        <label for="hwip_background_color"><?php esc_html_e( 'Background Color', 'hw-invoice-pdf' ); ?></label>
                        <input type="text" id="hwip_background_color" class="hwip-color-picker" name="background_color" value="<?php echo esc_attr( $settings['background_color'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label><?php esc_html_e( 'Background Image', 'hw-invoice-pdf' ); ?></label>
                        <div class="hwip-media-control" data-target="hwip_background_image_id">
                            <input type="hidden" id="hwip_background_image_id" name="background_image_id" value="<?php echo esc_attr( $bg_id ); ?>" />
                            <img src="<?php echo esc_url( $bg_url ); ?>" class="hwip-media-preview" <?php echo $bg_url ? '' : 'style="display:none;"'; ?> alt="" />
                            <div>
                                <button type="button" class="button hwip-upload-media" data-title="<?php esc_attr_e( 'Select Background', 'hw-invoice-pdf' ); ?>"><?php esc_html_e( 'Choose Image', 'hw-invoice-pdf' ); ?></button>
                                <button type="button" class="button hwip-remove-media"><?php esc_html_e( 'Remove', 'hw-invoice-pdf' ); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_accent_color"><?php esc_html_e( 'Accent Color', 'hw-invoice-pdf' ); ?></label>
                        <input type="text" id="hwip_accent_color" class="hwip-color-picker" name="accent_color" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" />
                    </div>
                    <div class="hwip-field">
                        <label for="hwip_font_family"><?php esc_html_e( 'Font Family', 'hw-invoice-pdf' ); ?></label>
                        <select id="hwip_font_family" name="font_family">
                            <option value="sans-serif" <?php selected( $settings['font_family'], 'sans-serif' ); ?>><?php esc_html_e( 'Default (Sans-serif)', 'hw-invoice-pdf' ); ?></option>
                            <option value="serif" <?php selected( $settings['font_family'], 'serif' ); ?>><?php esc_html_e( 'Serif', 'hw-invoice-pdf' ); ?></option>
                        </select>
                    </div>
                    <div class="hwip-field hwip-checkbox">
                        <label for="hwip_light_table_borders">
                            <input type="checkbox" id="hwip_light_table_borders" name="light_table_borders" value="1" <?php checked( ! empty( $settings['light_table_borders'] ) ); ?> />
                            <?php esc_html_e( 'Use lighter table borders', 'hw-invoice-pdf' ); ?>
                        </label>
                    </div>
                    <?php submit_button( __( 'Save Design', 'hw-invoice-pdf' ) ); ?>
                </form>

                <div class="hwip-card">
                    <h2><?php esc_html_e( 'Engine & Backfill Status', 'hw-invoice-pdf' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Last Backfill Run:', 'hw-invoice-pdf' ); ?></strong> <?php echo ! empty( $status['last_run'] ) ? esc_html( $status['last_run'] ) : esc_html__( 'Never', 'hw-invoice-pdf' ); ?></p>
                    <p><strong><?php esc_html_e( 'Last Processed Count:', 'hw-invoice-pdf' ); ?></strong> <?php echo isset( $status['processed'] ) ? esc_html( $status['processed'] ) : '0'; ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'hwip_run_backfill', 'hwip_backfill_nonce' ); ?>
                        <input type="hidden" name="action" value="hwip_run_backfill" />
                        <?php submit_button( __( 'Run Backfill Now', 'hw-invoice-pdf' ), 'secondary', 'submit', false ); ?>
                    </form>
                </div>

                <div class="hwip-card">
                    <h2><?php esc_html_e( 'Preview', 'hw-invoice-pdf' ); ?></h2>
                    <p><?php esc_html_e( 'Generate a preview invoice PDF using the latest order or sample data.', 'hw-invoice-pdf' ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'hwip_invoice_preview', 'hwip_invoice_preview_nonce' ); ?>
                        <input type="hidden" name="action" value="hwip_invoice_preview" />
                        <?php submit_button( __( 'Generate Preview Invoice', 'hw-invoice-pdf' ), 'primary', 'submit', false ); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle settings save submissions.
     */
    public function handle_save_settings() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'hw-invoice-pdf' ) );
        }

        check_admin_referer( 'hwip_save_settings', 'hwip_settings_nonce' );

        $settings = $this->plugin->get_settings();

        $settings['store_logo_id']       = isset( $_POST['store_logo_id'] ) ? absint( wp_unslash( $_POST['store_logo_id'] ) ) : $settings['store_logo_id'];
        $settings['store_name']          = isset( $_POST['store_name'] ) ? sanitize_text_field( wp_unslash( $_POST['store_name'] ) ) : $settings['store_name'];
        $settings['store_address']       = isset( $_POST['store_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['store_address'] ) ) : $settings['store_address'];
        $settings['store_phone']         = isset( $_POST['store_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['store_phone'] ) ) : $settings['store_phone'];
        $settings['store_email']         = isset( $_POST['store_email'] ) ? sanitize_email( wp_unslash( $_POST['store_email'] ) ) : $settings['store_email'];
        $settings['store_website']       = isset( $_POST['store_website'] ) ? esc_url_raw( wp_unslash( $_POST['store_website'] ) ) : $settings['store_website'];
        $settings['footer_text']         = isset( $_POST['footer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ) ) : $settings['footer_text'];
        $settings['background_color']    = isset( $_POST['background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['background_color'] ) ) : $settings['background_color'];
        $settings['background_image_id'] = isset( $_POST['background_image_id'] ) ? absint( wp_unslash( $_POST['background_image_id'] ) ) : $settings['background_image_id'];
        $settings['accent_color']        = isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : $settings['accent_color'];
        $settings['font_family']         = isset( $_POST['font_family'] ) && in_array( wp_unslash( $_POST['font_family'] ), array( 'sans-serif', 'serif' ), true ) ? wp_unslash( $_POST['font_family'] ) : 'sans-serif';
        $settings['light_table_borders'] = isset( $_POST['light_table_borders'] ) ? 1 : 0;

        if ( empty( $settings['background_color'] ) ) {
            $settings['background_color'] = '#ffffff';
        }
        if ( empty( $settings['accent_color'] ) ) {
            $settings['accent_color'] = '#222222';
        }

        $this->plugin->update_settings( $settings );

        $redirect = add_query_arg( 'hwip_notice', 'saved', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Add custom order action button.
     *
     * @param array    $actions Actions.
     * @param WC_Order $order   Order.
     *
     * @return array
     */
    public function add_order_action( $actions, $order ) {
        if ( ! current_user_can( 'manage_woocommerce' ) || ! $order instanceof WC_Order ) {
            return $actions;
        }

        $url = $this->get_invoice_url( $order->get_id(), 'hwip_invoice_pdf' );

        $actions['hwip_invoice_pdf'] = array(
            'url'    => $url,
            'name'   => __( 'Invoice PDF', 'hw-invoice-pdf' ),
            'action' => 'hwip_invoice_pdf',
        );

        $last_generated = (int) $order->get_meta( '_hw_invoice_last_generated', true );
        $modified       = $order->get_date_modified();
        $modified_time  = $modified ? $modified->getTimestamp() : ( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time() );

        if ( ! $last_generated || $modified_time > $last_generated ) {
            $regen_url = $this->get_invoice_url( $order->get_id(), 'hwip_invoice_regenerate_pdf' );

            $actions['hwip_invoice_pdf_regen'] = array(
                'url'    => $regen_url,
                'name'   => __( 'Regenerate Invoice PDF', 'hw-invoice-pdf' ),
                'action' => 'hwip_invoice_pdf_regen',
            );
        }

        return $actions;
    }

    /**
     * Register order metabox.
     */
    public function register_order_metabox() {
        add_meta_box(
            'hwip_invoice_metabox',
            __( 'HW Invoice PDF', 'hw-invoice-pdf' ),
            array( $this, 'render_order_metabox' ),
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Render order metabox.
     *
     * @param WP_Post $post Post.
     */
    public function render_order_metabox( $post ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $url = esc_url( $this->get_invoice_url( $post->ID ) );
        echo '<a class="button button-primary" href="' . $url . '">' . esc_html__( 'Download Invoice PDF', 'hw-invoice-pdf' ) . '</a>';
    }

    /**
     * Handle invoice PDF download.
     */
    public function handle_invoice_pdf() {
        $this->process_invoice_request( 'hwip_invoice_pdf' );
    }

    /**
     * Handle forced invoice regeneration.
     */
    public function handle_invoice_regenerate_pdf() {
        $this->process_invoice_request( 'hwip_invoice_regenerate_pdf' );
    }

    /**
     * Handle preview invoice generation.
     */
    public function handle_invoice_preview() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to run previews.', 'hw-invoice-pdf' ) );
        }

        check_admin_referer( 'hwip_invoice_preview', 'hwip_invoice_preview_nonce' );

        $order = $this->get_latest_order();

        if ( $order ) {
            $invoice_id = $this->plugin->invoice->build_invoice_id( $order );
        } else {
            $order      = $this->build_dummy_order();
            $invoice_id = 'HW-PREVIEW-' . gmdate( 'Ymd' );
        }

        $data = $this->plugin->invoice->get_invoice_data( $order, $invoice_id );
        $html = $this->plugin->invoice->render_invoice_html_from_data( $data );
        $this->plugin->pdf_generator->generate_invoice_pdf( $data, $invoice_id, $html );
    }

    /**
     * Handle manual backfill request.
     */
    public function handle_manual_backfill() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to run backfill.', 'hw-invoice-pdf' ) );
        }

        check_admin_referer( 'hwip_run_backfill', 'hwip_backfill_nonce' );

        $count = $this->plugin->cron ? $this->plugin->cron->run_manual_backfill() : 0;

        $notice = $count ? 'backfill_success' : 'backfill_none';

        $redirect = add_query_arg(
            array(
                'hwip_notice' => $notice,
                'processed'   => $count,
            ),
            admin_url( 'admin.php?page=' . self::PAGE_SLUG )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Display admin notices.
     */
    public function show_notices() {
        if ( empty( $_GET['hwip_notice'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $notice = sanitize_text_field( wp_unslash( $_GET['hwip_notice'] ) );
        $count  = isset( $_GET['processed'] ) ? absint( $_GET['processed'] ) : 0;
        $message = '';
        $class   = 'updated';

        switch ( $notice ) {
            case 'saved':
                $message = __( 'HW Invoice PDF settings saved successfully.', 'hw-invoice-pdf' );
                break;
            case 'backfill_success':
                /* translators: %d: processed orders count */
                $message = sprintf( __( 'Backfill completed. %d orders processed.', 'hw-invoice-pdf' ), $count );
                break;
            case 'backfill_none':
                $message = __( 'Backfill completed. No orders required updates.', 'hw-invoice-pdf' );
                break;
        }

        if ( $message ) {
            echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
        }
    }

    /**
     * Build admin invoice URL.
     *
     * @param int $order_id Order ID.
     *
     * @return string
     */
    protected function get_invoice_url( $order_id, $action = 'hwip_invoice_pdf' ) {
        $nonce = wp_create_nonce( $action . '_' . $order_id );

        return add_query_arg(
            array(
                'action'   => $action,
                'order_id' => (int) $order_id,
                'nonce'    => $nonce,
            ),
            admin_url( 'admin-post.php' )
        );
    }

    /**
     * Centralized invoice request handling.
     *
     * @param string $action_slug Action slug.
     */
    protected function process_invoice_request( $action_slug ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to download invoices.', 'hw-invoice-pdf' ) );
        }

        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

        if ( ! $order_id || ! wp_verify_nonce( $nonce, $action_slug . '_' . $order_id ) ) {
            wp_die( esc_html__( 'Invalid invoice request.', 'hw-invoice-pdf' ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'hw-invoice-pdf' ) );
        }

        $invoice_id = $this->plugin->invoice->build_invoice_id( $order );
        $data       = $this->plugin->invoice->get_invoice_data( $order, $invoice_id );
        $html       = $this->plugin->invoice->render_invoice_html_from_data( $data );

        $this->plugin->pdf_generator->generate_invoice_pdf( $data, $invoice_id, $html );

        $order->update_meta_data( '_hw_invoice_last_generated', time() );
        $order->save_meta_data();
    }

    /**
     * Fetch latest WooCommerce order.
     *
     * @return WC_Order|false
     */
    protected function get_latest_order() {
        $orders = wc_get_orders(
            array(
                'limit' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            )
        );

        if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
            return $orders[0];
        }

        return false;
    }

    /**
     * Build dummy order object for preview.
     *
     * @return WC_Order
     */
    protected function build_dummy_order() {
        $order = new WC_Order();
        $order->set_currency( get_woocommerce_currency() );
        $order->set_billing_first_name( 'Sample' );
        $order->set_billing_last_name( 'Customer' );
        $order->set_billing_phone( '+62 813-0000-0000' );
        $order->set_billing_address_1( 'Jl. Example 123' );
        $order->set_billing_city( 'Jakarta' );
        $order->set_billing_postcode( '12345' );
        $order->set_billing_country( 'ID' );
        $order->set_shipping_first_name( 'Sample' );
        $order->set_shipping_last_name( 'Customer' );
        $order->set_shipping_address_1( 'Jl. Example 123' );
        $order->set_shipping_city( 'Jakarta' );
        $order->set_shipping_postcode( '12345' );
        $order->set_shipping_country( 'ID' );
        $order->set_date_created( time() );
        $order->set_payment_method_title( __( 'Bank Transfer', 'hw-invoice-pdf' ) );
        $order->set_shipping_total( 5 );
        $order->set_total( 55 );

        $item = new WC_Order_Item_Product();
        $item->set_name( __( 'Sample Product', 'hw-invoice-pdf' ) );
        $item->set_quantity( 1 );
        $item->set_total( 50 );
        $item->set_subtotal( 50 );

        $order->set_items( array( $item ) );

        return $order;
    }
}
