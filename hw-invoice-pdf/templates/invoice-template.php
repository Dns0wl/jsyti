<?php
/**
 * Invoice template for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items            = isset( $data['items'] ) ? $data['items'] : array();
$background_color = isset( $data['background_color'] ) ? $data['background_color'] : '#ffffff';
$background_image = ! empty( $data['background_image'] ) ? $data['background_image'] : '';
$accent_color     = isset( $data['accent_color'] ) ? $data['accent_color'] : '#222222';
$font_family      = isset( $data['font_family'] ) ? $data['font_family'] : 'sans-serif';
$light_borders    = ! empty( $data['light_borders'] );

$wrapper_style = 'background-color:' . esc_attr( $background_color ) . ';';
if ( $background_image ) {
    $wrapper_style .= 'background-image:url(' . esc_url( $background_image ) . ');background-size:cover;background-position:center;';
}

$table_border = $light_borders ? '#dcdcdc' : '#999999';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: <?php echo esc_html( $font_family ); ?>; margin: 0; padding: 0; color: #1c1c1c; }
        .hwip-invoice { <?php echo esc_attr( $wrapper_style ); ?> min-height: 100vh; padding: 30px; }
        .hwip-inner { background: rgba(255,255,255,0.95); padding: 30px; }
        .hwip-header { display: flex; justify-content: space-between; border-bottom: 3px solid <?php echo esc_html( $accent_color ); ?>; padding-bottom: 20px; margin-bottom: 20px; }
        .hwip-header-left h1 { font-size: 32px; text-transform: uppercase; letter-spacing: 2px; margin: 0; color: <?php echo esc_html( $accent_color ); ?>; }
        .hwip-header-left img { max-height: 80px; margin-top: 10px; }
        .hwip-header-right { text-align: right; font-size: 12px; }
        .hwip-details { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .hwip-detail-box { width: 50%; border: 1px solid <?php echo esc_html( $table_border ); ?>; padding: 12px; }
        .hwip-detail-box h3 { margin: 0 0 8px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: <?php echo esc_html( $accent_color ); ?>; color: #fff; font-weight: 600; }
        th, td { padding: 10px; border: 1px solid <?php echo esc_html( $table_border ); ?>; font-size: 12px; text-align: left; vertical-align: top; }
        .hwip-summary { margin-top: 20px; }
        .hwip-summary div { margin-bottom: 6px; font-size: 13px; }
        .hwip-summary strong { display: inline-block; min-width: 140px; }
        .hwip-footer { margin-top: 30px; font-size: 11px; color: #666; text-align: center; border-top: 1px solid <?php echo esc_html( $table_border ); ?>; padding-top: 12px; }
        .hwip-product-variation { font-size: 10px; color: #555; margin-top: 4px; }
    </style>
</head>
<body>
<div class="hwip-invoice">
    <div class="hwip-inner">
        <div class="hwip-header">
            <div class="hwip-header-left">
                <h1><?php esc_html_e( 'Invoice', 'hw-invoice-pdf' ); ?></h1>
                <?php if ( ! empty( $data['logo_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $data['logo_url'] ); ?>" alt="<?php echo esc_attr( $data['store_name'] ); ?>" />
                <?php endif; ?>
            </div>
            <div class="hwip-header-right">
                <strong><?php echo esc_html( $data['company_name'] ); ?></strong><br />
                <?php echo nl2br( esc_html( $data['store_address'] ) ); ?><br />
                <?php esc_html_e( 'Email:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['store_email'] ); ?><br />
                <?php esc_html_e( 'Phone:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['store_phone'] ); ?><br />
                <?php esc_html_e( 'Website:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['store_website'] ); ?>
            </div>
        </div>

        <div class="hwip-details">
            <div class="hwip-detail-box">
                <p><strong><?php esc_html_e( 'Invoice ID:', 'hw-invoice-pdf' ); ?></strong> <?php echo esc_html( $data['invoice_id'] ); ?></p>
                <h3><?php esc_html_e( 'Invoice to:', 'hw-invoice-pdf' ); ?></h3>
                <p><?php echo esc_html( $data['customer_name'] ); ?><br /><?php esc_html_e( 'Phone:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['billing_phone'] ); ?></p>
            </div>
            <div class="hwip-detail-box">
                <h3><?php echo esc_html( $data['address_label'] ); ?>:</h3>
                <p><?php echo wp_kses_post( nl2br( $data['address_content'] ) ); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Product', 'hw-invoice-pdf' ); ?></th>
                    <th><?php esc_html_e( 'Regular Price', 'hw-invoice-pdf' ); ?></th>
                    <th><?php esc_html_e( 'Price', 'hw-invoice-pdf' ); ?></th>
                    <th><?php esc_html_e( 'QTY', 'hw-invoice-pdf' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'hw-invoice-pdf' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $item['name'] ); ?></strong>
                            <?php if ( ! empty( $item['variation'] ) ) : ?>
                                <div class="hwip-product-variation"><?php echo wp_kses_post( $item['variation'] ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo wp_kses_post( $item['regular_price'] ); ?></td>
                        <td><?php echo wp_kses_post( $item['price'] ); ?></td>
                        <td><?php echo esc_html( $item['qty'] ); ?></td>
                        <td><?php echo wp_kses_post( $item['total'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="hwip-summary">
            <div><strong><?php esc_html_e( 'Shipping Cost:', 'hw-invoice-pdf' ); ?></strong> <?php echo wp_kses_post( $data['shipping_total'] ); ?></div>
            <div><strong><?php esc_html_e( 'Total Invoice:', 'hw-invoice-pdf' ); ?></strong> <?php echo wp_kses_post( $data['order_total'] ); ?></div>
            <div><strong><?php esc_html_e( 'Payment Method:', 'hw-invoice-pdf' ); ?></strong> <?php echo esc_html( $data['payment_method'] ); ?></div>
        </div>

        <div class="hwip-footer">
            <?php echo nl2br( esc_html( $data['footer_text'] ) ); ?>
        </div>
    </div>
</div>
</body>
</html>
