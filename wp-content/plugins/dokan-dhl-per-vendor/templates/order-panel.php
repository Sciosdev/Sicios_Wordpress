<?php
/**
 * Vendor DHL order panel placeholder UI.
 *
 * @package Dokan_DHL_Per_Vendor\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$order_id         = isset( $order_id ) ? absint( $order_id ) : 0;
$has_label        = ! empty( $has_label );
$shipment_data    = isset( $shipment_data ) && is_array( $shipment_data ) ? $shipment_data : array();
$package_defaults = isset( $package_defaults ) && is_array( $package_defaults ) ? $package_defaults : array();
$download_url     = isset( $download_url ) ? $download_url : '';
$rest_nonce       = isset( $rest_nonce ) ? $rest_nonce : '';


$default_weight = isset( $package_defaults['weight'] ) ? $package_defaults['weight'] : '';
$default_length = isset( $package_defaults['length'] ) ? $package_defaults['length'] : '';
$default_width  = isset( $package_defaults['width'] ) ? $package_defaults['width'] : '';
$default_height = isset( $package_defaults['height'] ) ? $package_defaults['height'] : '';

$awb             = isset( $shipment_data['awb'] ) ? (string) $shipment_data['awb'] : '';
$tracking_status = isset( $shipment_data['tracking_status'] ) ? (string) $shipment_data['tracking_status'] : '';
$tracking_events = isset( $shipment_data['tracking_events'] ) && is_array( $shipment_data['tracking_events'] ) ? $shipment_data['tracking_events'] : array();
?>
<div
    class="dokan-dhl-order-panel"
    data-order-id="<?php echo esc_attr( $order_id ); ?>"
    data-rest-nonce="<?php echo esc_attr( $rest_nonce ); ?>"
    data-download-url="<?php echo esc_url( $download_url ); ?>"
    data-has-label="<?php echo $has_label ? '1' : '0'; ?>"
>
    <h3><?php esc_html_e( 'DHL Shipment', 'dokan-dhl-per-vendor' ); ?></h3>

    <div class="dokan-dhl-notice" style="display:none;"></div>

    <div class="dokan-dhl-create-section" <?php echo $has_label ? 'style="display:none;"' : ''; ?>>
        <p><?php esc_html_e( 'Provide package details to prepare your DHL shipping label.', 'dokan-dhl-per-vendor' ); ?></p>

        <div class="dokan-form-group dokan-dhl-dimensions">
            <label class="dokan-w3 dokan-control-label" for="dokan-dhl-weight-<?php echo esc_attr( $order_id ); ?>">
                <?php esc_html_e( 'Weight (kg)', 'dokan-dhl-per-vendor' ); ?>
            </label>
            <div class="dokan-w6 dokan-text-left">
                <input
                    id="dokan-dhl-weight-<?php echo esc_attr( $order_id ); ?>"
                    class="dokan-form-control dokan-dhl-field-weight"
                    type="number"
                    step="0.001"
                    min="0"
                    value="<?php echo esc_attr( $default_weight ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. 1.250', 'dokan-dhl-per-vendor' ); ?>"
                />
            </div>
        </div>

        <div class="dokan-form-group dokan-dhl-dimensions">
            <label class="dokan-w3 dokan-control-label" for="dokan-dhl-length-<?php echo esc_attr( $order_id ); ?>">
                <?php esc_html_e( 'Length (cm)', 'dokan-dhl-per-vendor' ); ?>
            </label>
            <div class="dokan-w6 dokan-text-left">
                <input
                    id="dokan-dhl-length-<?php echo esc_attr( $order_id ); ?>"
                    class="dokan-form-control dokan-dhl-field-length"
                    type="number"
                    step="0.1"
                    min="0"
                    value="<?php echo esc_attr( $default_length ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. 30', 'dokan-dhl-per-vendor' ); ?>"
                />
            </div>
        </div>

        <div class="dokan-form-group dokan-dhl-dimensions">
            <label class="dokan-w3 dokan-control-label" for="dokan-dhl-width-<?php echo esc_attr( $order_id ); ?>">
                <?php esc_html_e( 'Width (cm)', 'dokan-dhl-per-vendor' ); ?>
            </label>
            <div class="dokan-w6 dokan-text-left">
                <input
                    id="dokan-dhl-width-<?php echo esc_attr( $order_id ); ?>"
                    class="dokan-form-control dokan-dhl-field-width"
                    type="number"
                    step="0.1"
                    min="0"
                    value="<?php echo esc_attr( $default_width ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. 20', 'dokan-dhl-per-vendor' ); ?>"
                />
            </div>
        </div>

        <div class="dokan-form-group dokan-dhl-dimensions">
            <label class="dokan-w3 dokan-control-label" for="dokan-dhl-height-<?php echo esc_attr( $order_id ); ?>">
                <?php esc_html_e( 'Height (cm)', 'dokan-dhl-per-vendor' ); ?>
            </label>
            <div class="dokan-w6 dokan-text-left">
                <input
                    id="dokan-dhl-height-<?php echo esc_attr( $order_id ); ?>"
                    class="dokan-form-control dokan-dhl-field-height"
                    type="number"
                    step="0.1"
                    min="0"
                    value="<?php echo esc_attr( $default_height ); ?>"
                    placeholder="<?php esc_attr_e( 'e.g. 15', 'dokan-dhl-per-vendor' ); ?>"
                />
            </div>
        </div>

        <div class="dokan-form-group">
            <div class="dokan-w3"></div>
            <div class="dokan-w6 dokan-text-left">
                <button type="button" class="dokan-btn dokan-btn-theme dokan-dhl-create-label" data-order-id="<?php echo esc_attr( $order_id ); ?>">
                    <?php esc_html_e( 'Create DHL Label', 'dokan-dhl-per-vendor' ); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="dokan-dhl-existing-section" <?php echo $has_label ? '' : 'style="display:none;"'; ?>>
        <p class="dokan-dhl-awb">
            <?php esc_html_e( 'Existing shipment found. AWB:', 'dokan-dhl-per-vendor' ); ?>
            <strong class="dokan-dhl-awb-value"><?php echo esc_html( $awb ? $awb : __( 'Pending', 'dokan-dhl-per-vendor' ) ); ?></strong>
        </p>

        <div class="dokan-form-group dokan-dhl-actions">
            <button
                type="button"
                class="dokan-btn dokan-btn-theme dokan-dhl-download-label"
                data-order-id="<?php echo esc_attr( $order_id ); ?>"
                data-download-url="<?php echo esc_url( $download_url ); ?>"
                <?php echo $download_url ? '' : 'disabled="disabled"'; ?>
            >
                <?php esc_html_e( 'Download Label', 'dokan-dhl-per-vendor' ); ?>
            </button>
            <button
                type="button"
                class="dokan-btn dokan-btn-outline dokan-dhl-refresh-tracking"
                data-order-id="<?php echo esc_attr( $order_id ); ?>"
            >
                <?php esc_html_e( 'Update Tracking', 'dokan-dhl-per-vendor' ); ?>
            </button>
        </div>

        <div class="dokan-dhl-tracking">
            <p>
                <?php esc_html_e( 'Tracking status:', 'dokan-dhl-per-vendor' ); ?>
                <strong class="dokan-dhl-tracking-status-value"><?php echo esc_html( $tracking_status ? $tracking_status : __( 'Not available', 'dokan-dhl-per-vendor' ) ); ?></strong>
            </p>

            <?php if ( ! empty( $tracking_events ) ) : ?>
                <ul class="dokan-dhl-tracking-events">
                    <?php foreach ( $tracking_events as $event ) :
                        $description = isset( $event['description'] ) ? $event['description'] : '';
                        $location    = isset( $event['location'] ) ? $event['location'] : '';
                        $timestamp   = isset( $event['timestamp'] ) ? $event['timestamp'] : '';
                        ?>
                        <li>
                            <span class="dokan-dhl-tracking-event-description"><?php echo esc_html( $description ); ?></span>
                            <?php if ( $location ) : ?>
                                <span class="dokan-dhl-tracking-event-location">&mdash; <?php echo esc_html( $location ); ?></span>
                            <?php endif; ?>
                            <?php if ( $timestamp ) : ?>
                                <span class="dokan-dhl-tracking-event-timestamp">(<?php echo esc_html( $timestamp ); ?>)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="dokan-dhl-tracking-empty"><?php esc_html_e( 'No tracking updates yet.', 'dokan-dhl-per-vendor' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
