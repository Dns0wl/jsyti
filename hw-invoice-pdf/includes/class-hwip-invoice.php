<?php
/**
 * Invoice builder for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWIP_Invoice {

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
    }

    /**
     * Ensure an invoice ID exists for the provided order context.
     *
     * @param mixed $order_or_id Order instance or ID.
     */
    public function ensure_invoice_for_order( $order_or_id ) {
        $order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );

        if ( ! $order ) {
            return;
        }

        $this->build_invoice_id( $order );
    }

    /**
     * Build or fetch invoice ID for order.
     *
     * @param WC_Order $order Order object.
     *
     * @return string
     */
    public function build_invoice_id( $order ) {
        $invoice_number = $order->get_meta( '_hw_invoice_number', true );

        if ( ! empty( $invoice_number ) ) {
            return $invoice_number;
        }

        $date_created = $order->get_date_created();
        $date_part    = $date_created ? $date_created->date_i18n( 'Ymd' ) : gmdate( 'Ymd' );
        $order_number = preg_replace( '/[^0-9]/', '', $order->get_order_number() );

        $billing_first = $order->get_billing_first_name();
        $billing_last  = $order->get_billing_last_name();

        $abbr = '';
        if ( $billing_first ) {
            $abbr .= hwip_get_initial_letter( $billing_first );
        }
        if ( $billing_last ) {
            $abbr .= hwip_get_initial_letter( $billing_last );
        }
        if ( empty( $abbr ) ) {
            $abbr = 'HW';
        }

        $invoice_number = sprintf( 'HW-%s%s-%s', $date_part, $order_number, $abbr );
        $order->update_meta_data( '_hw_invoice_number', $invoice_number );
        $order->save_meta_data();

        return $invoice_number;
    }

    /**
     * Build data array used by templates or PDF rendering.
     *
     * @param WC_Order $order      Order instance.
     * @param string   $invoice_id Invoice identifier.
     *
     * @return array
     */
    public function get_invoice_data( $order, $invoice_id = '' ) {
        if ( empty( $invoice_id ) ) {
            $invoice_id = $this->build_invoice_id( $order );
        }

        $settings      = $this->plugin->get_settings();
        $store_name    = ! empty( $settings['store_name'] ) ? $settings['store_name'] : get_bloginfo( 'name', 'display' );
        $store_phone   = ! empty( $settings['store_phone'] ) ? $settings['store_phone'] : '+62 813-8370-8797';
        $store_email   = ! empty( $settings['store_email'] ) ? $settings['store_email'] : 'business@hayuwidyas.com';
        $store_website = ! empty( $settings['store_website'] ) ? $settings['store_website'] : home_url();
        $footer_text   = ! empty( $settings['footer_text'] ) ? $settings['footer_text'] : __( 'Thank you for appreciating and collecting quality handmade products from Indonesia by Hayu Widyas Handmade.', 'hw-invoice-pdf' );

        $store_address  = $this->get_store_address( $settings );
        $logo_id        = absint( $settings['store_logo_id'] );
        $logo_url       = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
        $bg_image_id    = absint( $settings['background_image_id'] );
        $bg_image_url   = $bg_image_id ? wp_get_attachment_image_url( $bg_image_id, 'full' ) : '';
        $background     = ! empty( $settings['background_color'] ) ? $settings['background_color'] : '#ffffff';
        $accent         = ! empty( $settings['accent_color'] ) ? $settings['accent_color'] : '#222222';
        $font_family    = ( ! empty( $settings['font_family'] ) && 'serif' === $settings['font_family'] ) ? 'serif' : 'sans-serif';
        $light_borders  = ! empty( $settings['light_table_borders'] );

        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_name = $customer_name ? $customer_name : __( 'Guest', 'hw-invoice-pdf' );
        $billing_phone = $order->get_billing_phone() ? $order->get_billing_phone() : __( 'N/A', 'hw-invoice-pdf' );
        $billing_addr  = $order->get_formatted_billing_address();
        $shipping_addr = $order->get_formatted_shipping_address();

        $address_label   = __( 'Billing Address', 'hw-invoice-pdf' );
        $address_content = $billing_addr ? wp_kses_post( $billing_addr ) : esc_html__( 'N/A', 'hw-invoice-pdf' );
        $address_plain   = $billing_addr ? wp_strip_all_tags( $billing_addr ) : __( 'N/A', 'hw-invoice-pdf' );

        if ( $shipping_addr && $shipping_addr !== $billing_addr ) {
            $address_label   = __( 'Shipping Address', 'hw-invoice-pdf' );
            $address_content = wp_kses_post( $shipping_addr );
            $address_plain   = wp_strip_all_tags( $shipping_addr );
        }

        $currency     = $order->get_currency();
        $items_output = array();

        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }

            $product       = $item->get_product();
            $product_name  = $item->get_name();
            $regular_price = $product && '' !== $product->get_regular_price() ? wc_price( $product->get_regular_price(), array( 'currency' => $currency ) ) : '&mdash;';
            $qty           = $item->get_quantity();
            $line_total    = $item->get_total() + $item->get_total_tax();
            $unit_price    = $qty ? $line_total / $qty : $line_total;
            $unit_display  = wc_price( $unit_price, array( 'currency' => $currency ) );
            $line_display  = wc_price( $line_total, array( 'currency' => $currency ) );
            $variation     = '';

            if ( $product && $product->is_type( 'variation' ) ) {
                $variation = wc_get_formatted_variation( $product, true, false, true );
            }

            $items_output[] = array(
                'name'          => $product_name,
                'variation'     => $variation,
                'regular_price' => $regular_price,
                'price'         => $unit_display,
                'qty'           => $qty,
                'total'         => $line_display,
            );
        }

        $shipping_total = wc_price( $order->get_shipping_total() + $order->get_shipping_tax(), array( 'currency' => $currency ) );
        $order_total    = wc_price( $order->get_total(), array( 'currency' => $currency ) );
        $payment_method = $order->get_payment_method_title();

        if ( ! $payment_method ) {
            $payment_method = __( 'Pending confirmation', 'hw-invoice-pdf' );
        } elseif ( 0 === strcasecmp( $payment_method, 'cash' ) ) {
            $payment_method = __( 'Manual payment', 'hw-invoice-pdf' );
        }
        $order_date      = $order->get_date_created();
        $order_timestamp = $order_date ? $order_date->getTimestamp() : current_time( 'timestamp' );
        $order_date_str  = $order_date ? $order_date->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $order_timestamp );
        $due_timestamp   = $order_timestamp + WEEK_IN_SECONDS;
        $due_date_str    = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $due_timestamp );

        return array(
            'invoice_id'        => $invoice_id,
            'logo_url'          => $logo_url,
            'store_name'        => $store_name,
            'company_name'      => 'PT Hayu Widyas Indonesia',
            'store_address'     => $store_address,
            'store_email'       => $store_email,
            'store_phone'       => $store_phone,
            'store_website'     => $store_website,
            'customer_name'     => $customer_name,
            'billing_phone'     => $billing_phone,
            'address_label'     => $address_label,
            'address_content'   => $address_content,
            'address_plain'     => $address_plain,
            'items'             => $items_output,
            'shipping_total'    => $shipping_total,
            'order_total'       => $order_total,
            'payment_method'    => $payment_method,
            'footer_text'       => $footer_text,
            'background_color'  => $background,
            'background_image'  => $bg_image_url,
            'accent_color'      => $accent,
            'font_family'       => $font_family,
            'light_borders'     => $light_borders,
            'order_date'        => $order_date_str,
            'due_date'          => $due_date_str,
        );
    }

    /**
     * Generate invoice HTML string.
     *
     * @param WC_Order $order      Order instance.
     * @param string   $invoice_id Invoice identifier.
     *
     * @return string
     */
    public function get_invoice_html( $order, $invoice_id = '' ) {
        $data = $this->get_invoice_data( $order, $invoice_id );

        return $this->render_invoice_html_from_data( $data );
    }

    /**
     * Render HTML for an invoice data payload.
     *
     * @param array $data Invoice data.
     *
     * @return string
     */
    public function render_invoice_html_from_data( $data ) {
        $template = $this->plugin->get_template_path( 'invoice-template.php' );

        ob_start();
        include $template;

        return (string) ob_get_clean();
    }

    /**
     * Get store address string.
     *
     * @param array $settings Settings array.
     *
     * @return string
     */
    private function get_store_address( $settings ) {
        if ( ! empty( $settings['store_address'] ) ) {
            return $settings['store_address'];
        }

        $address_1 = get_option( 'woocommerce_store_address' );
        $city      = get_option( 'woocommerce_store_city' );
        $country   = get_option( 'woocommerce_default_country' );
        $postcode  = get_option( 'woocommerce_store_postcode' );

        $parts = array_filter( array( $address_1, $city, $country, $postcode ) );

        return implode( '\n', $parts );
    }
}
