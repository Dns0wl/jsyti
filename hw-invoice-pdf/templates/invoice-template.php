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
        body { font-family: <?php echo esc_html( $font_family ); ?>; margin: 0; padding: 0; color: #1f2b2d; background: #f7f8fa; }
        .hwip-invoice { <?php echo esc_attr( $wrapper_style ); ?> min-height: 100vh; padding: 40px 24px; box-sizing: border-box; }
        .hwip-inner { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(18, 38, 52, 0.15); }
        .hwip-header { position: relative; background: linear-gradient(135deg, rgba(60,110,113,0.95), rgba(33,50,55,0.9)); color: #fff; padding: 32px 36px 70px; }
        .hwip-header::after { content: ''; position: absolute; bottom: 0; left: 30px; right: 30px; height: 60px; border-radius: 16px 16px 0 0; background: #fff; }
        .hwip-header-grid { position: relative; z-index: 2; display: flex; justify-content: space-between; gap: 30px; }
        .hwip-header-left { max-width: 60%; }
        .hwip-header-left h1 { font-size: 36px; letter-spacing: 4px; margin: 0 0 6px; text-transform: uppercase; }
        .hwip-header-left img { max-height: 70px; margin-top: 12px; filter: brightness(0) invert(1); }
        .hwip-header-meta { margin-top: 16px; font-size: 12px; line-height: 1.8; }
        .hwip-header-right { text-align: right; font-size: 13px; line-height: 1.6; }
        .hwip-meta-cards { background: #fff; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; padding: 0 36px 36px; margin-top: -46px; position: relative; z-index: 3; }
        .hwip-meta-card { border: 1px solid rgba(60, 110, 113, 0.15); border-radius: 12px; padding: 16px 18px; background: #fff; box-shadow: 0 14px 32px rgba(18, 38, 52, 0.05); }
        .hwip-meta-card span { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #758a8d; margin-bottom: 6px; }
        .hwip-meta-card strong { font-size: 15px; color: #102a2c; }
        .hwip-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; padding: 0 36px 36px; }
        .hwip-detail-box { border: 1px solid rgba(60, 110, 113, 0.2); border-radius: 14px; padding: 18px 20px; background: linear-gradient(120deg, rgba(60,110,113,0.04), rgba(255,255,255,0.9)); }
        .hwip-detail-box h3 { margin: 0 0 10px; font-size: 14px; letter-spacing: 0.5px; color: #3c6e71; text-transform: uppercase; }
        .hwip-detail-box p { margin: 0; font-size: 13px; line-height: 1.7; word-break: break-word; }
        .hwip-table-wrapper { padding: 0 36px 36px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; overflow: hidden; border-radius: 16px; }
        thead th { background: #102a2c; color: #fff; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 11px; }
        tbody td { background: #fff; }
        tbody tr:nth-child(odd) td { background: #f4f9f9; }
        th, td { padding: 14px 16px; border: none; font-size: 12px; text-align: left; vertical-align: top; line-height: 1.5; }
        th:first-child, td:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
        th:last-child, td:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; text-align: right; }
        .hwip-product-name { font-size: 13px; font-weight: 600; color: #1d2f30; }
        .hwip-product-variation { font-size: 11px; color: #6f7e80; margin-top: 4px; }
        .hwip-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; padding: 0 36px 36px; }
        .hwip-summary-card { border-radius: 14px; padding: 18px 20px; background: #0d1f21; color: #fff; }
        .hwip-summary-card:nth-child(2) { background: #3c6e71; }
        .hwip-summary-card:nth-child(3) { background: #1f4446; }
        .hwip-summary-card span { display: block; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px; color: rgba(255,255,255,0.75); }
        .hwip-summary-card strong { font-size: 20px; letter-spacing: 0.5px; }
        .hwip-footer { padding: 24px 36px 36px; background: linear-gradient(135deg, rgba(60,110,113,0.08), rgba(255,255,255,0.9)); font-size: 11px; color: #4d5b5d; line-height: 1.6; text-align: center; border-top: 1px solid rgba(60,110,113,0.15); }
        @media (max-width: 600px) {
            .hwip-header-grid, .hwip-header-right { text-align: left; flex-direction: column; }
            .hwip-header-grid { flex-direction: column; }
            .hwip-header::after { left: 16px; right: 16px; }
            .hwip-meta-cards, .hwip-details, .hwip-table-wrapper, .hwip-summary, .hwip-footer { padding-left: 20px; padding-right: 20px; }
            th, td { font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="hwip-invoice">
    <div class="hwip-inner">
        <div class="hwip-header">
            <div class="hwip-header-grid">
                <div class="hwip-header-left">
                    <h1><?php esc_html_e( 'Invoice', 'hw-invoice-pdf' ); ?></h1>
                    <div class="hwip-header-meta">
                        <?php echo esc_html( $data['company_name'] ); ?><br />
                        <?php echo esc_html( $data['store_email'] ); ?> · <?php echo esc_html( $data['store_phone'] ); ?>
                    </div>
                    <?php if ( ! empty( $data['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $data['logo_url'] ); ?>" alt="<?php echo esc_attr( $data['store_name'] ); ?>"/>
                    <?php endif; ?>
                </div>
                <div class="hwip-header-right">
                    <strong><?php echo esc_html( $data['store_name'] ); ?></strong><br />
                    <?php echo nl2br( esc_html( $data['store_address'] ) ); ?><br />
                    <?php esc_html_e( 'Website:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['store_website'] ); ?>
                </div>
            </div>
        </div>

        <div class="hwip-meta-cards">
            <div class="hwip-meta-card">
                <span><?php esc_html_e( 'Invoice ID', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['invoice_id'] ); ?></strong>
            </div>
            <div class="hwip-meta-card">
                <span><?php esc_html_e( 'Issued on', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['order_date'] ); ?></strong>
            </div>
            <div class="hwip-meta-card">
                <span><?php esc_html_e( 'Due date', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['due_date'] ); ?></strong>
            </div>
            <div class="hwip-meta-card">
                <span><?php esc_html_e( 'Payment method', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['payment_method'] ); ?></strong>
            </div>
        </div>

        <div class="hwip-details">
            <div class="hwip-detail-box">
                <h3><?php esc_html_e( 'Invoice to', 'hw-invoice-pdf' ); ?></h3>
                <p>
                    <?php echo esc_html( $data['customer_name'] ); ?><br />
                    <?php esc_html_e( 'Phone:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['billing_phone'] ); ?>
                </p>
            </div>
            <div class="hwip-detail-box">
                <h3><?php echo esc_html( $data['address_label'] ); ?></h3>
                <p><?php echo wp_kses_post( nl2br( $data['address_content'] ) ); ?></p>
            </div>
        </div>

        <div class="hwip-table-wrapper">
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
                            <div class="hwip-product-name"><?php echo esc_html( $item['name'] ); ?></div>
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
        </div>

        <div class="hwip-summary">
            <div class="hwip-summary-card">
                <span><?php esc_html_e( 'Shipping', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo wp_kses_post( $data['shipping_total'] ); ?></strong>
            </div>
            <div class="hwip-summary-card">
                <span><?php esc_html_e( 'Invoice total', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo wp_kses_post( $data['order_total'] ); ?></strong>
            </div>
            <div class="hwip-summary-card">
                <span><?php esc_html_e( 'Payment method', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['payment_method'] ); ?></strong>
            </div>
        </div>

        <div class="hwip-footer">
            <?php echo nl2br( esc_html( $data['footer_text'] ) ); ?>
        </div>
    </div>
</div>
</body>
</html>
