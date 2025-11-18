<?php
/**
 * Custom PDF renderer for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWIP_PDF_Generator {

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
     * Generate and stream invoice PDF.
     *
     * @param array|string $invoice_payload Invoice data or legacy HTML string.
     * @param string       $invoice_id      Invoice identifier.
     * @param string       $invoice_html    Optional HTML used for previews or logging.
     */
    public function generate_invoice_pdf( $invoice_payload, $invoice_id, $invoice_html = '' ) {
        if ( empty( $invoice_payload ) ) {
            wp_die( esc_html__( 'Unable to render invoice. Please refresh and try again.', 'hw-invoice-pdf' ) );
        }

        if ( is_string( $invoice_payload ) ) {
            $invoice_payload = array(
                'invoice_id' => $invoice_id,
                'store_name' => get_bloginfo( 'name', 'display' ),
                'raw_text'   => wp_strip_all_tags( $invoice_payload ),
            );
        }

        $document = new HWIP_Simple_PDF();
        $this->render_invoice_document( $document, $invoice_payload, $invoice_id );

        $pdf_content = $document->output();
        $filename    = 'Invoice-' . sanitize_title( $invoice_id ) . '.pdf';

        if ( ! headers_sent() ) {
            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Content-Length: ' . strlen( $pdf_content ) );
        }

        echo $pdf_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Render invoice data into the PDF document.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $data     Invoice data payload.
     * @param string          $invoice_id Invoice identifier.
     */
    protected function render_invoice_document( HWIP_Simple_PDF $document, array $data, $invoice_id ) {
        if ( ! empty( $data['raw_text'] ) ) {
            $document->write_text_block( $data['raw_text'], 12 );
            return;
        }

        $store_name    = ! empty( $data['store_name'] ) ? $data['store_name'] : get_bloginfo( 'name', 'display' );
        $company_name  = ! empty( $data['company_name'] ) ? $data['company_name'] : $store_name;
        $store_phone   = ! empty( $data['store_phone'] ) ? $data['store_phone'] : __( 'N/A', 'hw-invoice-pdf' );
        $store_email   = ! empty( $data['store_email'] ) ? $data['store_email'] : get_bloginfo( 'admin_email' );
        $store_site    = ! empty( $data['store_website'] ) ? $data['store_website'] : home_url();
        $store_addr    = ! empty( $data['store_address'] ) ? $data['store_address'] : '';
        $order_date    = ! empty( $data['order_date'] ) ? $data['order_date'] : date_i18n( get_option( 'date_format' ) );
        $due_date      = ! empty( $data['due_date'] ) ? $data['due_date'] : '';
        $customer      = ! empty( $data['customer_name'] ) ? $data['customer_name'] : __( 'Customer', 'hw-invoice-pdf' );
        $address_text  = ! empty( $data['address_plain'] ) ? $data['address_plain'] : __( 'N/A', 'hw-invoice-pdf' );
        $billing_phone = ! empty( $data['billing_phone'] ) ? $data['billing_phone'] : __( 'N/A', 'hw-invoice-pdf' );
        $items         = ! empty( $data['items'] ) ? $data['items'] : array();
        $footer_text   = ! empty( $data['footer_text'] ) ? $data['footer_text'] : '';
        $shipping      = ! empty( $data['shipping_total'] ) ? wp_strip_all_tags( $data['shipping_total'] ) : '';
        $order_total   = ! empty( $data['order_total'] ) ? wp_strip_all_tags( $data['order_total'] ) : '';
        $payment       = ! empty( $data['payment_method'] ) ? $data['payment_method'] : __( 'Not provided', 'hw-invoice-pdf' );

        $store_name    = wp_strip_all_tags( $store_name );
        $company_name  = wp_strip_all_tags( $company_name );
        $store_phone   = wp_strip_all_tags( $store_phone );
        $store_email   = sanitize_email( $store_email );
        $store_site    = $store_site ? esc_url_raw( $store_site ) : '';
        $store_addr    = wp_strip_all_tags( $store_addr );
        $order_date    = wp_strip_all_tags( $order_date );
        $due_date      = wp_strip_all_tags( $due_date );
        $customer      = wp_strip_all_tags( $customer );
        $address_text  = wp_strip_all_tags( $address_text );
        $billing_phone = wp_strip_all_tags( $billing_phone );
        $payment       = wp_strip_all_tags( $payment );
        $footer_text   = wp_strip_all_tags( $footer_text );

        $palette = $this->get_color_palette();

        $this->render_modern_header( $document, $palette, $invoice_id, $store_name, $store_site );
        $this->render_meta_summary( $document, $palette, $invoice_id, $order_date, $due_date );

        $billing_lines = array(
            $customer,
            $address_text,
            sprintf( /* translators: %s billing phone */ __( 'Phone: %s', 'hw-invoice-pdf' ), $billing_phone ),
        );

        $company_lines = array_filter(
            array(
                $company_name,
                $store_addr,
                $store_phone ? sprintf( /* translators: %s store phone */ __( 'Support: %s', 'hw-invoice-pdf' ), $store_phone ) : '',
                $store_email ? sprintf( /* translators: %s store email */ __( 'Email: %s', 'hw-invoice-pdf' ), $store_email ) : '',
                $store_site ? sprintf( /* translators: %s store website */ __( 'Website: %s', 'hw-invoice-pdf' ), $store_site ) : '',
            )
        );

        $this->render_contact_section( $document, $palette, $billing_lines, $company_lines );

        $this->render_items_table( $document, $items, $palette );
        $this->render_payment_panel( $document, $palette, $shipping, $payment, $order_total, $due_date );
        $this->render_footer_band( $document, $palette, $footer_text, $store_site, $store_email );
    }

    /**
     * Provide a consistent color palette.
     *
     * @return array
     */
    protected function get_color_palette() {
        return array(
            'ink'              => '#16171c',
            'muted'            => '#84868f',
            'subtle'           => '#b9bbc2',
            'line'             => '#e2e3e7',
            'pill_bg'          => '#f3f3f4',
            'table_header_bg'  => '#f7f7f8',
            'table_header_text'=> '#5e6167',
            'contact_bg'       => '#fbfbfc',
            'payment_bg'       => '#f2f3f5',
            'accent'           => '#111113',
            'footer_bg'        => '#101010',
            'footer_text'      => '#f6f6f6',
        );
    }

    /**
     * Format the badge text shown in the header.
     *
     * @param string $label Store label.
     *
     * @return string
     */
    protected function format_store_badge_label( $label ) {
        $clean = strtoupper( wp_strip_all_tags( (string) $label ) );

        if ( function_exists( 'mb_strimwidth' ) ) {
            $clean = mb_strimwidth( $clean, 0, 34, '…', 'UTF-8' );
        } elseif ( strlen( $clean ) > 34 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
            $clean = substr( $clean, 0, 31 ) . '…';
        }

        return $clean;
    }

    /**
     * Render the minimal hero header.
     *
     * @param HWIP_Simple_PDF $document  PDF helper.
     * @param array           $palette   Color palette.
     * @param string          $invoice_id Invoice identifier.
     * @param string          $store_name Store name.
     * @param string          $store_site Store website.
     */
    protected function render_modern_header( HWIP_Simple_PDF $document, array $palette, $invoice_id, $store_name, $store_site ) {
        $left    = $document->get_margin( 'left' );
        $width   = $document->get_content_width();
        $start_y = $document->get_current_y();

        $document->write_text_block( strtoupper( __( 'Invoice', 'hw-invoice-pdf' ) ), 34, array( 'style' => 'bold', 'color' => $palette['ink'] ) );
        $document->write_text_block( sprintf( __( 'No. %s', 'hw-invoice-pdf' ), $invoice_id ), 12, array( 'color' => $palette['muted'] ) );

        if ( $store_site ) {
            $document->write_text_block( $store_site, 11, array( 'color' => $palette['subtle'], 'spacing_after' => 6 ) );
        } else {
            $document->add_spacer( 6 );
        }

        $pill_width  = min( 220, max( 140, $width * 0.35 ) );
        $badge_text  = $store_name ? $this->format_store_badge_label( $store_name ) : '';
        $badge_lines = $badge_text ? $document->get_wrapped_lines( $badge_text, 10, $pill_width - 24 ) : array();
        $line_count  = $badge_text ? max( 1, count( $badge_lines ) ) : 0;
        $pill_height = max( 30, 12 + ( $line_count * 12 ) );
        $pill_x      = $left + $width - $pill_width;
        $pill_y      = $start_y + 4;

        $document->draw_rectangle( $pill_x, $pill_y, $pill_width, $pill_height, $palette['pill_bg'], $palette['line'], 0.7 );
        if ( $badge_text ) {
            foreach ( $badge_lines as $index => $line ) {
                $document->write_text_at( $line, 10, $pill_x + 12, $pill_y + 12 + ( $index * 12 ), array( 'style' => 'bold', 'color' => $palette['ink'] ) );
            }
        }

        $document->add_horizontal_rule( 8, 12 );
    }

    /**
     * Render invoice date summary row.
     *
     * @param HWIP_Simple_PDF $document  PDF helper.
     * @param array           $palette   Color palette.
     * @param string          $invoice_id Invoice identifier.
     * @param string          $order_date Order date.
     * @param string          $due_date   Due date.
     */
    protected function render_meta_summary( HWIP_Simple_PDF $document, array $palette, $invoice_id, $order_date, $due_date ) {
        $left    = $document->get_margin( 'left' );
        $width   = $document->get_content_width();
        $height  = 68;
        $start_y = $document->get_current_y();

        $document->add_spacer( $height );
        $document->draw_rectangle( $left, $start_y, $width, $height, '#ffffff', $palette['line'], 0.6 );

        $columns = array(
            array(
                'label' => __( 'Invoice Date', 'hw-invoice-pdf' ),
                'value' => $order_date,
            ),
            array(
                'label' => __( 'Invoice Due', 'hw-invoice-pdf' ),
                'value' => $due_date ? $due_date : __( 'Pending', 'hw-invoice-pdf' ),
            ),
            array(
                'label' => __( 'Invoice Number', 'hw-invoice-pdf' ),
                'value' => $invoice_id,
            ),
        );

        $col_width = $width / count( $columns );
        $label_y   = $start_y + 22;
        $value_y   = $label_y + 16;

        foreach ( $columns as $index => $column ) {
            $x     = $left + ( $index * $col_width ) + 12;
            $value = $column['value'] ? $column['value'] : __( 'Not set', 'hw-invoice-pdf' );
            $document->write_text_at( strtoupper( $column['label'] ), 9, $x, $label_y, array( 'style' => 'bold', 'color' => $palette['muted'] ) );
            $document->write_wrapped_text_at( $value, 13, $x, $value_y, $col_width - 18, 14, array( 'color' => $palette['ink'] ) );
        }

        $document->add_spacer( 18 );
    }

    /**
     * Render the billing/store contact block.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $palette  Palette.
     * @param array           $billing_lines Billing lines.
     * @param array           $company_lines Store lines.
     */
    protected function render_contact_section( HWIP_Simple_PDF $document, array $palette, array $billing_lines, array $company_lines ) {
        $billing = array_filter( array_map( 'trim', $billing_lines ) );
        $company = array_filter( array_map( 'trim', $company_lines ) );

        if ( empty( $billing ) && empty( $company ) ) {
            return;
        }

        $line_height = 14;
        $padding     = 18;
        $left        = $document->get_margin( 'left' );
        $width       = $document->get_content_width();
        $start_y     = $document->get_current_y();
        $col_width   = $width / 2;
        $inner_width = $col_width - 28;

        $billing_height = $this->measure_contact_column_height( $document, $billing, 12, $inner_width, $line_height );
        $company_height = $this->measure_contact_column_height( $document, $company, 12, $inner_width, $line_height );
        $content_height = max( $line_height, max( $billing_height, $company_height ) );
        $block_height   = $content_height + ( 2 * $padding ) + 20;

        $document->add_spacer( $block_height );
        $document->draw_rectangle( $left, $start_y, $width, $block_height, $palette['contact_bg'], $palette['line'], 0.6 );

        $title_y = $start_y + $padding;

        $document->write_text_at( strtoupper( __( 'Billing To', 'hw-invoice-pdf' ) ), 9, $left + 14, $title_y, array( 'style' => 'bold', 'color' => $palette['muted'] ) );
        $document->write_text_at( strtoupper( __( 'Artisan', 'hw-invoice-pdf' ) ), 9, $left + $col_width + 14, $title_y, array( 'style' => 'bold', 'color' => $palette['muted'] ) );

        $base_y         = $title_y + 16;
        $billing_cursor = $base_y;
        $company_cursor = $base_y;

        foreach ( $billing as $line ) {
            $line_height_used = $document->write_wrapped_text_at( $line, 12, $left + 14, $billing_cursor, $inner_width, 14, array( 'color' => $palette['ink'] ) );
            $billing_cursor  += max( $line_height, $line_height_used ) + 2;
        }

        foreach ( $company as $line ) {
            $line_height_used = $document->write_wrapped_text_at( $line, 12, $left + $col_width + 14, $company_cursor, $inner_width, 14, array( 'color' => $palette['ink'] ) );
            $company_cursor  += max( $line_height, $line_height_used ) + 2;
        }

        $document->add_spacer( 20 );
    }

    /**
     * Measure the required height for contact columns.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $lines    Text lines.
     * @param int             $font_size Font size.
     * @param float           $max_width Column width.
     * @param int             $line_height Line height.
     *
     * @return float
     */
    protected function measure_contact_column_height( HWIP_Simple_PDF $document, array $lines, $font_size, $max_width, $line_height ) {
        $height = 0;

        foreach ( $lines as $line ) {
            $line = trim( (string) $line );

            if ( '' === $line ) {
                continue;
            }

            $wrapped = $document->get_wrapped_lines( $line, $font_size, $max_width );
            $count   = max( 1, count( $wrapped ) );
            $height += ( $count * $line_height ) + 2;
        }

        return $height;
    }

    /**
     * Render the order items table.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $items    Line items.
     * @param array           $palette  Color palette.
     */
    protected function render_items_table( HWIP_Simple_PDF $document, array $items, array $palette ) {
        $left          = $document->get_margin( 'left' );
        $width         = $document->get_content_width();
        $columns       = $this->get_table_columns( $left, $width );
        $product_width = $this->get_product_column_width( $columns );
        $start_y       = $document->get_current_y();

        $header_height = 34;
        $document->add_spacer( $header_height );
        $document->draw_rectangle( $left, $start_y, $width, $header_height, $palette['table_header_bg'], $palette['line'], 0.6 );
        $baseline = $start_y + 22;

        $document->write_text_at( __( 'Description', 'hw-invoice-pdf' ), 10, $columns['product'], $baseline, array( 'style' => 'bold', 'color' => $palette['table_header_text'] ) );
        $document->write_text_at( __( 'Qty', 'hw-invoice-pdf' ), 10, $columns['qty'], $baseline, array( 'style' => 'bold', 'color' => $palette['table_header_text'] ) );
        $document->write_text_at( __( 'Price', 'hw-invoice-pdf' ), 10, $columns['price'], $baseline, array( 'style' => 'bold', 'color' => $palette['table_header_text'] ) );
        $document->write_text_at( __( 'Total', 'hw-invoice-pdf' ), 10, $columns['total'], $baseline, array( 'style' => 'bold', 'color' => $palette['table_header_text'] ) );

        if ( empty( $items ) ) {
            $row_height = 36;
            $row_y      = $document->get_current_y();
            $document->draw_rectangle( $left, $row_y, $width, $row_height, '#ffffff', $palette['line'], 0.8 );
            $document->write_text_at( __( 'No line items available for this invoice.', 'hw-invoice-pdf' ), 11, $columns['product'], $row_y + 22, array( 'color' => $palette['muted'] ) );
            $document->add_spacer( $row_height );

            return;
        }

        foreach ( $items as $item ) {
            $name      = ! empty( $item['name'] ) ? wp_strip_all_tags( $item['name'] ) : __( 'Product', 'hw-invoice-pdf' );
            $variation = ! empty( $item['variation'] ) ? wp_strip_all_tags( $item['variation'] ) : '';
            $qty       = isset( $item['qty'] ) ? intval( $item['qty'] ) : 0;
            $price     = ! empty( $item['price'] ) ? wp_strip_all_tags( $item['price'] ) : __( 'N/A', 'hw-invoice-pdf' );
            $total     = ! empty( $item['total'] ) ? wp_strip_all_tags( $item['total'] ) : __( 'N/A', 'hw-invoice-pdf' );

            $name_lines      = $document->get_wrapped_lines( $name, 12, $product_width );
            $variation_lines = $variation ? $document->get_wrapped_lines( $variation, 10, $product_width ) : array();
            $name_height     = max( 1, count( $name_lines ) ) * 14;
            $variation_height = $variation ? max( 1, count( $variation_lines ) ) * 12 + 4 : 0;
            $row_height      = max( 40, 18 + $name_height + $variation_height );
            $row_y      = $document->get_current_y();
            $document->draw_rectangle( $left, $row_y, $width, $row_height, '#ffffff', $palette['line'], 0.4 );

            $baseline   = $row_y + 20;
            $name_block = $document->write_wrapped_text_at( $name, 12, $columns['product'], $baseline, $product_width, 14, array( 'style' => 'bold', 'color' => $palette['ink'] ) );

            if ( $variation ) {
                $document->write_wrapped_text_at( $variation, 10, $columns['product'], $baseline + $name_block + 2, $product_width, 12, array( 'color' => $palette['muted'] ) );
            }

            $document->write_text_at( (string) $qty, 12, $columns['qty'], $baseline, array( 'color' => $palette['ink'] ) );
            $document->write_text_at( $price, 12, $columns['price'], $baseline, array( 'color' => $palette['ink'] ) );
            $document->write_text_at( $total, 12, $columns['total'], $baseline, array( 'style' => 'bold', 'color' => $palette['ink'] ) );

            $document->add_spacer( $row_height );
        }

        $document->add_spacer( 18 );
    }

    /**
     * Provide table column starting positions.
     *
     * @param float $left  Left margin.
     * @param float $width Content width.
     *
     * @return array
     */
    protected function get_table_columns( $left, $width ) {
        return array(
            'product' => $left + 16,
            'qty'     => $left + ( $width * 0.6 ),
            'price'   => $left + ( $width * 0.74 ),
            'total'   => $left + ( $width * 0.88 ),
        );
    }

    /**
     * Determine usable width for the product column.
     *
     * @param array $columns Column map.
     *
     * @return float
     */
    protected function get_product_column_width( array $columns ) {
        $qty_start = isset( $columns['qty'] ) ? (float) $columns['qty'] : 0;
        $product   = isset( $columns['product'] ) ? (float) $columns['product'] : 0;

        return max( 140, $qty_start - $product - 12 );
    }

    /**
     * Render payment details block.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $palette  Palette.
     * @param string          $shipping Shipping total.
     * @param string          $payment  Payment method.
     * @param string          $order_total Order total.
     * @param string          $due_date Due date string.
     */
    protected function render_payment_panel( HWIP_Simple_PDF $document, array $palette, $shipping, $payment, $order_total, $due_date ) {
        $left    = $document->get_margin( 'left' );
        $width   = $document->get_content_width();
        $line_height = 20;
        $rows        = 2; // Payment + total.

        if ( $shipping ) {
            $rows++;
        }

        if ( $due_date ) {
            $rows++;
        }

        $padding = 18;
        $height  = ( $rows * $line_height ) + ( 2 * $padding ) + 12;
        $start_y = $document->get_current_y();

        $document->add_spacer( $height );
        $document->draw_rectangle( $left, $start_y, $width, $height, $palette['payment_bg'], $palette['line'], 0.6 );

        $label_x = $left + 18;
        $value_x = $left + $width - 180;
        $current = $start_y + $padding + 6;

        $document->write_text_at( __( 'Payment Method', 'hw-invoice-pdf' ), 12, $label_x, $current, array( 'style' => 'bold', 'color' => $palette['ink'] ) );
        $document->write_text_at( $payment ? $payment : __( 'Pending confirmation', 'hw-invoice-pdf' ), 12, $value_x, $current, array( 'color' => $palette['ink'] ) );

        $current += $line_height;

        if ( $shipping ) {
            $document->write_text_at( __( 'Shipping', 'hw-invoice-pdf' ), 11, $label_x, $current, array( 'color' => $palette['muted'] ) );
            $document->write_text_at( $shipping, 11, $value_x, $current, array( 'color' => $palette['ink'] ) );
            $current += $line_height;
        }

        if ( $due_date ) {
            $document->write_text_at( __( 'Due Date', 'hw-invoice-pdf' ), 11, $label_x, $current, array( 'color' => $palette['muted'] ) );
            $document->write_text_at( $due_date, 11, $value_x, $current, array( 'color' => $palette['ink'] ) );
            $current += $line_height;
        }

        $document->write_text_at( __( 'Grand Total', 'hw-invoice-pdf' ), 12, $label_x, $current + 4, array( 'style' => 'bold', 'color' => $palette['ink'] ) );
        $document->write_text_at( $order_total ? $order_total : __( 'Pending', 'hw-invoice-pdf' ), 20, $value_x, $current, array( 'style' => 'bold', 'color' => $palette['accent'] ) );

        $document->add_spacer( 24 );
    }

    /**
     * Render footer band with terms.
     *
     * @param HWIP_Simple_PDF $document PDF helper.
     * @param array           $palette  Palette.
     * @param string          $footer_text Footer text.
     * @param string          $store_site Store site.
     * @param string          $store_email Store email.
     */
    protected function render_footer_band( HWIP_Simple_PDF $document, array $palette, $footer_text, $store_site, $store_email ) {
        $left      = $document->get_margin( 'left' );
        $width     = $document->get_content_width();
        $band_height = 64;
        $start_y   = $document->get_current_y();

        $document->add_spacer( 20 );
        $start_y = $document->get_current_y();
        $document->add_spacer( $band_height );
        $document->draw_rectangle( $left, $start_y, $width, $band_height, $palette['footer_bg'], $palette['footer_bg'], 0 );

        $text_y = $start_y + 26;
        $terms  = $footer_text ? $footer_text : __( 'Terms & Conditions: Please settle payment within 30 days to avoid penalties.', 'hw-invoice-pdf' );
        $document->write_text_at( wp_strip_all_tags( $terms ), 11, $left + 16, $text_y, array( 'color' => $palette['footer_text'] ) );

        $contact = array_filter( array( $store_site, $store_email ) );
        if ( ! empty( $contact ) ) {
            $document->write_text_at( implode( '  •  ', $contact ), 10, $left + 16, $text_y + 18, array( 'color' => $palette['subtle'] ) );
        }
    }
}

/**
 * Extremely small PDF helper tailored for invoices.
 */
class HWIP_Simple_PDF {

    /**
     * Page width in points (A4 portrait).
     *
     * @var float
     */
    protected $page_width = 595.28;

    /**
     * Page height in points.
     *
     * @var float
     */
    protected $page_height = 841.89;

    /**
     * Stored page contents.
     *
     * @var array
     */
    protected $pages = array();

    /**
     * Index of the current page.
     *
     * @var int
     */
    protected $current_page = 0;

    /**
     * Current Y coordinate measured from top.
     *
     * @var float
     */
    protected $current_y = 0.0;

    /**
     * Margins.
     *
     * @var array
     */
    protected $margins = array(
        'left'   => 40,
        'right'  => 40,
        'top'    => 40,
        'bottom' => 40,
    );

    /**
     * Font map for quick lookups.
     *
     * @var array
     */
    protected $fonts = array(
        'regular' => 'F1',
        'bold'    => 'F2',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->add_page();
    }

    /**
     * Add a blank page.
     */
    protected function add_page() {
        $this->pages[]     = array( 'content' => '' );
        $this->current_page = count( $this->pages ) - 1;
        $this->current_y    = $this->margins['top'];
    }

    /**
     * Content width between margins.
     *
     * @return float
     */
    public function get_content_width() {
        return $this->page_width - $this->margins['left'] - $this->margins['right'];
    }

    /**
     * Retrieve a margin value.
     *
     * @param string $side Side name.
     *
     * @return float
     */
    public function get_margin( $side ) {
        return isset( $this->margins[ $side ] ) ? $this->margins[ $side ] : 0;
    }

    /**
     * Current Y coordinate from the top of the page.
     *
     * @return float
     */
    public function get_current_y() {
        return $this->current_y;
    }

    /**
     * Ensure there is enough space for upcoming content.
     *
     * @param float $height Height needed.
     */
    protected function ensure_space( $height ) {
        $height = max( 0, (float) $height );
        $limit  = $this->page_height - $this->margins['bottom'];

        if ( $this->current_y + $height > $limit ) {
            $this->add_page();
        }
    }

    /**
     * Add a vertical spacer.
     *
     * @param float $height Height to add.
     */
    public function add_spacer( $height ) {
        $height = max( 0, (float) $height );
        $this->ensure_space( $height );
        $this->current_y += $height;
    }

    /**
     * Draw a horizontal rule at the current position.
     *
     * @param float $gap_before Space before.
     * @param float $gap_after  Space after.
     */
    public function add_horizontal_rule( $gap_before = 8, $gap_after = 8 ) {
        $this->add_spacer( $gap_before );
        $y = $this->current_y;
        $this->draw_line( $this->margins['left'], $y, $this->page_width - $this->margins['right'], $y, 0.6 );
        $this->add_spacer( $gap_after );
    }

    /**
     * Draw a line with absolute coordinates.
     *
     * @param float $x1 Start X.
     * @param float $y1 Start Y from top.
     * @param float $x2 End X.
     * @param float $y2 End Y from top.
     * @param float $width Stroke width.
     */
    protected function draw_line( $x1, $y1, $x2, $y2, $width = 0.5 ) {
        $y1 = $this->page_height - $y1;
        $y2 = $this->page_height - $y2;
        $this->append_content( sprintf( "%.2f w %.2f %.2f m %.2f %.2f l S\n", $width, $x1, $y1, $x2, $y2 ) );
    }

    /**
     * Draw a rectangle with optional fill and stroke colors.
     *
     * @param float       $x            Left position.
     * @param float       $y            Top position.
     * @param float       $width        Width.
     * @param float       $height       Height.
     * @param string|null $fill_color   Fill color.
     * @param string|null $stroke_color Stroke color.
     * @param float       $line_width   Stroke width.
     */
    public function draw_rectangle( $x, $y, $width, $height, $fill_color = null, $stroke_color = null, $line_width = 0.5 ) {
        $rect_y = $this->page_height - $y - $height;
        $ops    = '';

        if ( $line_width ) {
            $ops .= sprintf( "%.2f w\n", $line_width );
        }

        if ( $stroke_color ) {
            $ops .= $this->color_command( $stroke_color, true ) . "\n";
        }

        if ( $fill_color ) {
            $ops .= $this->color_command( $fill_color, false ) . "\n";
        }

        $ops .= sprintf( "%.2f %.2f %.2f %.2f re ", $x, $rect_y, $width, $height );

        if ( $fill_color && $stroke_color ) {
            $ops .= "B\n";
        } elseif ( $fill_color ) {
            $ops .= "f\n";
        } else {
            $ops .= "S\n";
        }

        $this->append_content( $ops );
    }

    /**
     * Append raw content to the current page.
     *
     * @param string $content Content string.
     */
    protected function append_content( $content ) {
        $this->pages[ $this->current_page ]['content'] .= $content;
    }

    /**
     * Write a block of text with word wrapping.
     *
     * @param string $text     Text to print.
     * @param float  $font_size Font size.
     * @param array  $options   Additional options.
     */
    public function write_text_block( $text, $font_size = 12, $options = array() ) {
        $defaults = array(
            'style'          => 'regular',
            'x'              => null,
            'max_width'      => null,
            'line_height'    => null,
            'spacing_before' => 0,
            'spacing_after'  => 0,
            'color'          => null,
        );

        $options     = array_merge( $defaults, $options );
        $font_size   = (float) $font_size;
        $line_height = $options['line_height'] ? (float) $options['line_height'] : $font_size + 4;
        $max_width   = $options['max_width'] ? (float) $options['max_width'] : $this->page_width - $this->margins['left'] - $this->margins['right'];
        $x           = null === $options['x'] ? $this->margins['left'] : (float) $options['x'];
        $style       = isset( $this->fonts[ $options['style'] ] ) ? $options['style'] : 'regular';
        $font_key    = $this->fonts[ $style ];
        $text        = (string) $text;

        if ( $options['spacing_before'] ) {
            $this->add_spacer( $options['spacing_before'] );
        }

        $lines = $this->wrap_text( $text, $font_size, $max_width );
        $count = max( 1, count( $lines ) );
        $this->ensure_space( $line_height * $count );

        $color_cmd = $this->color_command( $options['color'], false );

        foreach ( $lines as $line ) {
            $this->write_line( $line, $font_size, $line_height, $x, $font_key, $color_cmd );
        }

        if ( $options['spacing_after'] ) {
            $this->add_spacer( $options['spacing_after'] );
        }
    }

    /**
     * Internal helper to write a single line.
     *
     * @param string $text       Text content.
     * @param float  $font_size  Font size.
     * @param float  $line_height Line height.
     * @param float  $x          X position.
     * @param string $font_key   Font identifier.
     */
    protected function write_line( $text, $font_size, $line_height, $x, $font_key, $color_cmd = '' ) {
        $y    = $this->page_height - $this->current_y;
        $text = $this->escape_text( $text );

        if ( $color_cmd ) {
            $this->append_content( $color_cmd . "\n" );
        }

        $this->append_content( sprintf( 'BT /%s %.2f Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n', $font_key, $font_size, $x, $y, $text ) );
        $this->current_y += $line_height;
    }

    /**
     * Write text at a fixed coordinate.
     *
     * @param string $text      Text content.
     * @param float  $font_size Font size.
     * @param float  $x         X position.
     * @param float  $y         Y position from top.
     * @param array  $options   Extra options.
     */
    public function write_text_at( $text, $font_size, $x, $y, $options = array() ) {
        $defaults = array(
            'style' => 'regular',
            'color' => null,
        );

        $options   = array_merge( $defaults, $options );
        $style     = isset( $this->fonts[ $options['style'] ] ) ? $options['style'] : 'regular';
        $font_key  = $this->fonts[ $style ];
        $color_cmd = $this->color_command( $options['color'], false );
        $pdf_y     = $this->page_height - $y;
        $text      = $this->escape_text( (string) $text );

        if ( $color_cmd ) {
            $this->append_content( $color_cmd . "\n" );
        }

        $this->append_content( sprintf( 'BT /%s %.2f Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n', $font_key, $font_size, $x, $pdf_y, $text ) );
    }

    /**
     * Write wrapped text relative to a coordinate.
     *
     * @param string $text       Text content.
     * @param float  $font_size  Font size.
     * @param float  $x          X coordinate.
     * @param float  $y          Starting Y coordinate.
     * @param float  $max_width  Maximum width for wrapping.
     * @param float  $line_height Line height.
     * @param array  $options    Text options.
     *
     * @return float Height consumed.
     */
    public function write_wrapped_text_at( $text, $font_size, $x, $y, $max_width, $line_height = 14, $options = array() ) {
        $lines  = $this->wrap_text( (string) $text, $font_size, $max_width );
        $count  = max( 1, count( $lines ) );
        $height = 0;

        foreach ( $lines as $index => $line ) {
            $this->write_text_at( $line, $font_size, $x, $y + ( $index * $line_height ), $options );
            $height = ( $index + 1 ) * $line_height;
        }

        return $height;
    }

    /**
     * Expose wrapped lines for layout calculations.
     *
     * @param string $text      Text content.
     * @param float  $font_size Font size.
     * @param float  $max_width Maximum width.
     *
     * @return array
     */
    public function get_wrapped_lines( $text, $font_size, $max_width ) {
        return $this->wrap_text( (string) $text, $font_size, $max_width );
    }

    /**
     * Wrap text to fit within max width.
     *
     * @param string $text      Text.
     * @param float  $font_size Font size.
     * @param float  $max_width Maximum width in points.
     *
     * @return array
     */
    protected function wrap_text( $text, $font_size, $max_width ) {
        $clean = trim( preg_replace( '/\s+/u', ' ', $text ) );

        if ( '' === $clean ) {
            return array( '' );
        }

        $approx_char_width = max( 0.1, $font_size * 0.5 );
        $max_chars         = max( 1, (int) floor( $max_width / $approx_char_width ) );
        $wrapped           = wordwrap( $clean, $max_chars, "\n", true );

        return explode( "\n", $wrapped );
    }

    /**
     * Escape text for PDF output.
     *
     * @param string $text Text to escape.
     *
     * @return string
     */
    protected function escape_text( $text ) {
        $text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
        $text = preg_replace( "/[\r\n]+/", ' ', $text );

        return $text;
    }

    /**
     * Convert a color to a PDF command string.
     *
     * @param mixed $color  Hex string or RGB array.
     * @param bool  $stroke Whether to apply to stroke (true) or fill (false).
     *
     * @return string
     */
    protected function color_command( $color, $stroke = false ) {
        $rgb = $this->normalize_color( $color );

        if ( empty( $rgb ) ) {
            return '';
        }

        $op = $stroke ? 'RG' : 'rg';

        return sprintf( '%.3f %.3f %.3f %s', $rgb[0], $rgb[1], $rgb[2], $op );
    }

    /**
     * Normalize supported color formats into RGB arrays.
     *
     * @param mixed $color Color value.
     *
     * @return array|null
     */
    protected function normalize_color( $color ) {
        if ( empty( $color ) ) {
            return null;
        }

        if ( is_array( $color ) && isset( $color[0], $color[1], $color[2] ) ) {
            return array( (float) $color[0], (float) $color[1], (float) $color[2] );
        }

        $color = trim( (string) $color );

        if ( '#' === substr( $color, 0, 1 ) ) {
            $hex = substr( $color, 1 );
            if ( 3 === strlen( $hex ) ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            if ( 6 === strlen( $hex ) ) {
                $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
                $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
                $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

                return array( $r, $g, $b );
            }
        }

        if ( preg_match( '/rgb\s*\((\d+),\s*(\d+),\s*(\d+)\)/i', $color, $matches ) ) {
            return array( $matches[1] / 255, $matches[2] / 255, $matches[3] / 255 );
        }

        return null;
    }

    /**
     * Compile the stored pages into a PDF string.
     *
     * @return string
     */
    public function output() {
        if ( empty( $this->pages ) ) {
            $this->add_page();
        }

        $page_count = count( $this->pages );
        $objects    = array();
        $offsets    = array();
        $object_id  = 1;

        $font_regular_id = $object_id;
        $objects[ $object_id++ ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $font_bold_id = $object_id;
        $objects[ $object_id++ ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $content_ids = array();
        foreach ( $this->pages as $page ) {
            $content = $page['content'];
            if ( '' === trim( $content ) ) {
                $content = 'BT /F1 12 Tf 1 0 0 1 ' . $this->margins['left'] . ' ' . ( $this->page_height - $this->margins['top'] ) . " Tm () Tj ET\n";
            }

            $content_length         = strlen( $content );
            $objects[ $object_id ]  = "<< /Length {$content_length} >>\nstream\n{$content}\nendstream";
            $content_ids[]          = $object_id;
            $object_id++;
        }

        $page_refs = array();
        $pages_obj_id = 2 * $page_count + 3;

        foreach ( $content_ids as $content_id ) {
            $page_id = $object_id;
            $resources = sprintf( '<< /Font << /F1 %d 0 R /F2 %d 0 R >> >>', $font_regular_id, $font_bold_id );
            $objects[ $page_id ] = sprintf(
                '<< /Type /Page /Parent %1$d 0 R /MediaBox [0 0 %2$.2f %3$.2f] /Contents %4$d 0 R /Resources %5$s >>',
                $pages_obj_id,
                $this->page_width,
                $this->page_height,
                $content_id,
                $resources
            );
            $page_refs[] = $page_id . ' 0 R';
            $object_id++;
        }

        $objects[ $object_id ] = '<< /Type /Pages /Kids [' . implode( ' ', $page_refs ) . '] /Count ' . $page_count . ' >>';
        $pages_obj_id = $object_id;
        $object_id++;

        $objects[ $object_id ] = '<< /Type /Catalog /Pages ' . $pages_obj_id . ' 0 R >>';
        $catalog_obj_id        = $object_id;
        $object_id++;

        $pdf = "%PDF-1.4\n";
        for ( $i = 1; $i < $object_id; $i++ ) {
            $offsets[ $i ] = strlen( $pdf );
            $pdf          .= $i . " 0 obj\n" . $objects[ $i ] . "\nendobj\n";
        }

        $xref_offset = strlen( $pdf );
        $pdf        .= 'xref' . "\n";
        $pdf        .= '0 ' . $object_id . "\n";
        $pdf        .= "0000000000 65535 f \n";

        for ( $i = 1; $i < $object_id; $i++ ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
        }

        $pdf .= 'trailer' . "\n";
        $pdf .= '<< /Size ' . $object_id . ' /Root ' . $catalog_obj_id . " 0 R >>\n";
        $pdf .= 'startxref' . "\n" . $xref_offset . "\n";
        $pdf .= '%%EOF';

        return $pdf;
    }
}
