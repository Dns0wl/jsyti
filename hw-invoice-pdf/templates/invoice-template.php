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
        body { font-family: <?php echo esc_html( $font_family ); ?>; margin: 0; padding: 30px; color: #1f2b2d; background: #f2f4f5; }
        .hwip-invoice { <?php echo esc_attr( $wrapper_style ); ?> max-width: 900px; margin: 0 auto; }
        .hwip-inner { background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 80px rgba(23, 50, 52, 0.18); position: relative; }
        .hwip-header { background: #122225; color: #fff; padding: 40px; position: relative; }
        .hwip-header::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #0c1618, #3c6e71); opacity: 0.92; }
        .hwip-header::after { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.12), transparent 45%), radial-gradient(circle at 80% 0, rgba(255,255,255,0.18), transparent 45%); opacity: 0.8; }
        .hwip-header-content { position: relative; z-index: 2; display: flex; gap: 28px; justify-content: space-between; align-items: flex-start; }
        .hwip-brand { max-width: 55%; }
        .hwip-brand h1 { margin: 0; font-size: 42px; letter-spacing: 6px; text-transform: uppercase; }
        .hwip-brand p { margin: 10px 0 0; font-size: 13px; line-height: 1.7; opacity: 0.85; }
        .hwip-brand img { margin-top: 16px; max-height: 72px; filter: brightness(0) invert(1); }
        .hwip-meta { text-align: right; }
        .hwip-meta strong { display: block; font-size: 15px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px; }
        .hwip-meta ul { list-style: none; padding: 0; margin: 0; font-size: 12px; line-height: 1.9; }
        .hwip-meta li span { color: rgba(255,255,255,0.65); display: inline-block; min-width: 110px; text-transform: uppercase; letter-spacing: 0.5px; }
        .hwip-pills { background: #fff; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; padding: 0 40px; position: relative; margin-top: -40px; }
        .hwip-pill { padding: 16px 18px; border-radius: 14px; border: 1px solid rgba(18, 34, 37, 0.06); box-shadow: 0 20px 40px rgba(18, 34, 37, 0.08); }
        .hwip-pill span { display: block; font-size: 11px; text-transform: uppercase; color: #8b9a9c; letter-spacing: 0.8px; margin-bottom: 6px; }
        .hwip-pill strong { font-size: 16px; color: #133235; }
        .hwip-details { padding: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 22px; }
        .hwip-card { border: 1px solid rgba(18, 34, 37, 0.08); border-radius: 18px; padding: 22px; background: linear-gradient(145deg, rgba(60, 110, 113, 0.06), rgba(255, 255, 255, 0.95)); }
        .hwip-card h3 { margin: 0 0 12px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #3c6e71; }
        .hwip-card p { margin: 0; font-size: 13px; line-height: 1.8; word-break: break-word; }
        .hwip-table-wrapper { padding: 0 40px 40px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: #f7faf9; color: #546163; padding: 14px 16px; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 2px solid #3c6e71; }
        tbody td { padding: 18px 16px; border-bottom: 1px solid #e1e6e7; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(odd) { background: #fbfdfd; }
        th:first-child, td:first-child { border-top-left-radius: 10px; }
        th:last-child, td:last-child { text-align: right; }
        .hwip-product-name { font-size: 13px; font-weight: 600; color: #122225; }
        .hwip-product-variation { font-size: 11px; color: #7c8b8c; margin-top: 4px; }
        .hwip-summary { padding: 0 40px 45px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .hwip-summary-card { border-radius: 18px; padding: 22px; background: #122225; color: #fff; position: relative; overflow: hidden; }
        .hwip-summary-card::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(60,110,113,0.7), transparent); opacity: 0.8; }
        .hwip-summary-card-content { position: relative; z-index: 2; }
        .hwip-summary-card span { display: block; font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px; color: rgba(255,255,255,0.7); }
        .hwip-summary-card strong { font-size: 22px; letter-spacing: 0.5px; }
        .hwip-footer { padding: 22px 40px 40px; border-top: 1px solid rgba(18, 34, 37, 0.08); font-size: 11px; color: #5b6668; text-align: center; line-height: 1.6; background: linear-gradient(120deg, rgba(60,110,113,0.08), rgba(255,255,255,0.6)); }
        @media (max-width: 680px) {
            body { padding: 15px; }
            .hwip-header-content { flex-direction: column; text-align: left; }
            .hwip-meta { text-align: left; width: 100%; }
            .hwip-pills, .hwip-details, .hwip-table-wrapper, .hwip-summary, .hwip-footer { padding-left: 22px; padding-right: 22px; }
            table, thead, tbody, th, td, tr { display: block; text-align: right; }
            thead { display: none; }
            tbody tr { margin-bottom: 14px; border: 1px solid #e1e6e7; border-radius: 12px; padding: 10px; background: #fff; }
            tbody td { border: none; display: flex; justify-content: space-between; padding: 8px 0; font-size: 11px; }
            tbody td::before { content: attr(data-label); text-transform: uppercase; letter-spacing: 0.5px; color: #6a777a; font-weight: 600; }
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
