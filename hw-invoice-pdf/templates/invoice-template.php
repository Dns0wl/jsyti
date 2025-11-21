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
        body { font-family: <?php echo esc_html( $font_family ); ?>; margin: 0; padding: 24px; color: #0f1c1e; background: #eef2f3; }
        .hwip-invoice { <?php echo esc_attr( $wrapper_style ); ?> max-width: 900px; margin: 0 auto; }
        .hwip-inner { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(18, 34, 37, 0.16); position: relative; }
        .hwip-header { background: linear-gradient(135deg, #0f1c1e, #3c6e71); color: #fff; padding: 32px 34px 30px; position: relative; }
        .hwip-header::after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 18% 20%, rgba(255,255,255,0.18), transparent 35%), radial-gradient(circle at 90% 15%, rgba(255,255,255,0.12), transparent 45%); opacity: 0.8; }
        .hwip-header-content { position: relative; z-index: 2; display: table; width: 100%; table-layout: fixed; }
        .hwip-brand, .hwip-meta { display: table-cell; vertical-align: top; }
        .hwip-brand { width: 60%; padding-right: 18px; }
        .hwip-brand h1 { margin: 0 0 6px; font-size: 38px; letter-spacing: 5px; text-transform: uppercase; }
        .hwip-brand p { margin: 6px 0 0; font-size: 13px; line-height: 1.7; opacity: 0.9; word-break: break-word; }
        .hwip-brand img { margin-top: 14px; max-height: 70px; filter: brightness(0) invert(1); }
        .hwip-meta { width: 40%; text-align: right; }
        .hwip-meta strong { display: block; font-size: 15px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px; word-break: break-word; }
        .hwip-meta ul { list-style: none; padding: 0; margin: 0; font-size: 12px; line-height: 1.8; }
        .hwip-meta li { margin: 0 0 4px; word-break: break-word; }
        .hwip-meta li span { color: rgba(255,255,255,0.72); display: inline-block; min-width: 86px; text-transform: uppercase; letter-spacing: 0.5px; }
        .hwip-pills { background: #fff; display: flex; flex-wrap: wrap; gap: 12px; padding: 0 30px 6px; position: relative; margin-top: -22px; }
        .hwip-pill { flex: 1 1 180px; padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(18, 34, 37, 0.06); background: linear-gradient(135deg, rgba(60,110,113,0.12), rgba(255,255,255,0.95)); box-shadow: 0 12px 30px rgba(18, 34, 37, 0.08); }
        .hwip-pill span { display: block; font-size: 10px; text-transform: uppercase; color: #708183; letter-spacing: 0.8px; margin-bottom: 6px; }
        .hwip-pill strong { font-size: 15px; color: #0f1c1e; word-break: break-word; }
        .hwip-details { padding: 28px 30px 8px; display: flex; flex-wrap: wrap; gap: 18px; }
        .hwip-card { flex: 1 1 250px; border: 1px solid rgba(18, 34, 37, 0.08); border-radius: 16px; padding: 18px; background: linear-gradient(145deg, rgba(60, 110, 113, 0.06), rgba(255, 255, 255, 0.96)); }
        .hwip-card h3 { margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.9px; color: #3c6e71; }
        .hwip-card p { margin: 0; font-size: 13px; line-height: 1.7; word-break: break-word; white-space: pre-line; }
        .hwip-table-wrapper { padding: 0 30px 28px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed; }
        thead th { background: #f6f9f9; color: #4d5a5c; padding: 12px 10px; text-transform: uppercase; letter-spacing: 0.7px; border-bottom: 2px solid #3c6e71; text-align: left; }
        tbody td { padding: 14px 10px; border-bottom: 1px solid #e1e6e7; vertical-align: top; word-break: break-word; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(odd) { background: #fbfdfd; }
        th:last-child, td:last-child { text-align: right; }
        .hwip-product-name { font-size: 13px; font-weight: 600; color: #0f1c1e; line-height: 1.5; }
        .hwip-product-variation { font-size: 11px; color: #6c7c7e; margin-top: 4px; line-height: 1.5; }
        .hwip-summary { padding: 0 30px 30px; display: flex; flex-wrap: wrap; gap: 14px; }
        .hwip-summary-card { flex: 1 1 220px; border-radius: 16px; padding: 18px; background: #122225; color: #fff; position: relative; overflow: hidden; }
        .hwip-summary-card::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(60,110,113,0.7), transparent); opacity: 0.8; }
        .hwip-summary-card-content { position: relative; z-index: 2; }
        .hwip-summary-card span { display: block; font-size: 10px; letter-spacing: 0.7px; text-transform: uppercase; margin-bottom: 6px; color: rgba(255,255,255,0.72); }
        .hwip-summary-card strong { font-size: 20px; letter-spacing: 0.4px; word-break: break-word; }
        .hwip-footer { padding: 18px 30px 30px; border-top: 1px solid rgba(18, 34, 37, 0.08); font-size: 11px; color: #5b6668; text-align: center; line-height: 1.6; background: linear-gradient(120deg, rgba(60,110,113,0.08), rgba(255,255,255,0.65)); word-break: break-word; }
        @media (max-width: 760px) {
            body { padding: 16px; }
            .hwip-header-content { display: block; }
            .hwip-brand, .hwip-meta { display: block; width: 100%; text-align: left; padding-right: 0; }
            .hwip-meta { margin-top: 14px; }
            .hwip-meta ul { padding-left: 0; }
            .hwip-pills, .hwip-details, .hwip-table-wrapper, .hwip-summary, .hwip-footer { padding-left: 18px; padding-right: 18px; }
            table, thead, tbody, th, td, tr { display: block; width: 100%; }
            thead { display: none; }
            tbody tr { margin-bottom: 12px; border: 1px solid #e1e6e7; border-radius: 12px; padding: 10px; background: #fff; }
            tbody td { border: none; display: flex; justify-content: space-between; padding: 8px 4px; font-size: 11px; text-align: right; }
            tbody td::before { content: attr(data-label); text-transform: uppercase; letter-spacing: 0.5px; color: #6a777a; font-weight: 600; padding-right: 10px; text-align: left; }
            .hwip-summary-card { min-height: 0; }
        }
    </style>
</head>
<body>
<div class="hwip-invoice">
    <div class="hwip-inner">
        <div class="hwip-header">
            <div class="hwip-header-content">
                <div class="hwip-brand">
                    <h1><?php esc_html_e( 'Invoice', 'hw-invoice-pdf' ); ?></h1>
                    <p>
                        <?php echo esc_html( $data['company_name'] ); ?><br />
                        <?php echo esc_html( $data['store_email'] ); ?> · <?php echo esc_html( $data['store_phone'] ); ?>
                    </p>
                    <?php if ( ! empty( $data['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $data['logo_url'] ); ?>" alt="<?php echo esc_attr( $data['store_name'] ); ?>" />
                    <?php endif; ?>
                </div>
                <div class="hwip-meta">
                    <strong><?php echo esc_html( $data['store_name'] ); ?></strong>
                    <ul>
                        <li><span><?php esc_html_e( 'Address', 'hw-invoice-pdf' ); ?></span><?php echo esc_html( $data['store_address'] ); ?></li>
                        <li><span><?php esc_html_e( 'Website', 'hw-invoice-pdf' ); ?></span><?php echo esc_html( $data['store_website'] ); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="hwip-pills">
            <div class="hwip-pill">
                <span><?php esc_html_e( 'Invoice ID', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['invoice_id'] ); ?></strong>
            </div>
            <div class="hwip-pill">
                <span><?php esc_html_e( 'Issued On', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['order_date'] ); ?></strong>
            </div>
            <div class="hwip-pill">
                <span><?php esc_html_e( 'Due Date', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['due_date'] ); ?></strong>
            </div>
            <div class="hwip-pill">
                <span><?php esc_html_e( 'Payment Method', 'hw-invoice-pdf' ); ?></span>
                <strong><?php echo esc_html( $data['payment_method'] ); ?></strong>
            </div>
        </div>

        <div class="hwip-details">
            <div class="hwip-card">
                <h3><?php esc_html_e( 'Invoice To', 'hw-invoice-pdf' ); ?></h3>
                <p>
                    <?php echo esc_html( $data['customer_name'] ); ?><br />
                    <?php esc_html_e( 'Phone:', 'hw-invoice-pdf' ); ?> <?php echo esc_html( $data['billing_phone'] ); ?>
                </p>
            </div>
            <div class="hwip-card">
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
                        <td data-label="<?php esc_attr_e( 'Product', 'hw-invoice-pdf' ); ?>">
                            <div class="hwip-product-name"><?php echo esc_html( $item['name'] ); ?></div>
                            <?php if ( ! empty( $item['variation'] ) ) : ?>
                                <div class="hwip-product-variation"><?php echo wp_kses_post( $item['variation'] ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Regular Price', 'hw-invoice-pdf' ); ?>"><?php echo wp_kses_post( $item['regular_price'] ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Price', 'hw-invoice-pdf' ); ?>"><?php echo wp_kses_post( $item['price'] ); ?></td>
                        <td data-label="<?php esc_attr_e( 'QTY', 'hw-invoice-pdf' ); ?>"><?php echo esc_html( $item['qty'] ); ?></td>
                        <td data-label="<?php esc_attr_e( 'Total', 'hw-invoice-pdf' ); ?>"><?php echo wp_kses_post( $item['total'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div class="hwip-summary">
            <div class="hwip-summary-card">
                <div class="hwip-summary-card-content">
                    <span><?php esc_html_e( 'Shipping', 'hw-invoice-pdf' ); ?></span>
                    <strong><?php echo wp_kses_post( $data['shipping_total'] ); ?></strong>
                </div>
            </div>
            <div class="hwip-summary-card">
                <div class="hwip-summary-card-content">
                    <span><?php esc_html_e( 'Invoice Total', 'hw-invoice-pdf' ); ?></span>
                    <strong><?php echo wp_kses_post( $data['order_total'] ); ?></strong>
                </div>
            </div>
            <div class="hwip-summary-card">
                <div class="hwip-summary-card-content">
                    <span><?php esc_html_e( 'Payment Method', 'hw-invoice-pdf' ); ?></span>
                    <strong><?php echo esc_html( $data['payment_method'] ); ?></strong>
                </div>
            </div>
        </div>

        <div class="hwip-footer">
            <?php echo nl2br( esc_html( $data['footer_text'] ) ); ?>
        </div>
    </div>
</div>
</body>
</html>
