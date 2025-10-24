<?php
/**
 * Vendor DHL settings form.
 *
 * @package Dokan_DHL_Per_Vendor\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$values                 = isset( $values ) && is_array( $values ) ? $values : array();
$field_groups           = isset( $field_groups ) && is_array( $field_groups ) ? $field_groups : array();
$clear_secret_checked   = ! empty( $clear_secret_checked );
?>
<div class="dokan-dhl-settings">
    <h2><?php esc_html_e( 'DHL Settings', 'dokan-dhl-per-vendor' ); ?></h2>

    <form method="post" class="dokan-form-horizontal">
        <?php wp_nonce_field( 'dokan_dhl_save_settings', 'dokan_dhl_settings_nonce' ); ?>
        <input type="hidden" name="dokan_dhl_settings_submit" value="1" />

        <?php foreach ( $field_groups as $group_key => $group ) : ?>
            <fieldset class="dokan-form-group dokan-dhl-fieldset dokan-dhl-fieldset-<?php echo esc_attr( $group_key ); ?>">
                <?php if ( ! empty( $group['title'] ) ) : ?>
                    <legend><?php echo esc_html( $group['title'] ); ?></legend>
                <?php endif; ?>

                <?php foreach ( $group['fields'] as $field_key => $field ) :
                    $field_id      = 'dokan_dhl_' . $field_key;
                    $field_value   = isset( $values[ $field_key ] ) ? $values[ $field_key ] : '';
                    $type          = isset( $field['type'] ) ? $field['type'] : 'text';
                    $autocomplete  = isset( $field['autocomplete'] ) ? $field['autocomplete'] : 'on';
                    $step          = isset( $field['step'] ) ? $field['step'] : '';
                    $min           = isset( $field['min'] ) ? $field['min'] : '';
                    $maxlength     = isset( $field['maxlength'] ) ? (int) $field['maxlength'] : 0;
                    $placeholder   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
                    $description   = isset( $field['description'] ) ? $field['description'] : '';
                    $display_value = 'password' === $type ? '' : $field_value;
                    ?>
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label" for="<?php echo esc_attr( $field_id ); ?>">
                            <?php echo esc_html( $field['label'] ); ?>
                        </label>
                        <div class="dokan-w6 dokan-text-left">
                            <input
                                id="<?php echo esc_attr( $field_id ); ?>"
                                class="dokan-form-control"
                                name="dokan_dhl[<?php echo esc_attr( $field_key ); ?>]"
                                type="<?php echo esc_attr( $type ); ?>"
                                value="<?php echo esc_attr( $display_value ); ?>"
                                <?php if ( ! empty( $autocomplete ) ) : ?>autocomplete="<?php echo esc_attr( $autocomplete ); ?>"<?php endif; ?>
                                <?php if ( $step ) : ?>step="<?php echo esc_attr( $step ); ?>"<?php endif; ?>
                                <?php if ( $min ) : ?>min="<?php echo esc_attr( $min ); ?>"<?php endif; ?>
                                <?php if ( $maxlength ) : ?>maxlength="<?php echo esc_attr( $maxlength ); ?>"<?php endif; ?>
                                <?php if ( $placeholder ) : ?>placeholder="<?php echo esc_attr( $placeholder ); ?>"<?php endif; ?>
                            />
                            <?php if ( 'api_secret' === $field_key ) : ?>
                                <label class="dokan-dhl-clear-secret">
                                    <input type="checkbox" name="dokan_dhl_clear_api_secret" value="1" <?php checked( $clear_secret_checked ); ?> />
                                    <?php esc_html_e( 'Remove the saved secret on save', 'dokan-dhl-per-vendor' ); ?>
                                </label>
                            <?php endif; ?>

                            <?php if ( $description ) : ?>
                                <p class="help-block description"><?php echo esc_html( $description ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ( 'credentials' === $group_key ) : ?>
                    <div class="dokan-form-group dokan-dhl-test-connection-row">
                        <div class="dokan-w3"></div>
                        <div class="dokan-w6 dokan-text-left">
                            <button
                                type="button"
                                class="dokan-btn dokan-btn-secondary dokan-dhl-test-connection"
                                data-action="dokan_dhl_test_connection"
                                data-nonce="<?php echo esc_attr( $test_connection_nonce ); ?>"
                                data-vendor-id="<?php echo esc_attr( isset( $vendor_id ) ? $vendor_id : 0 ); ?>"
                                data-default-text="<?php echo esc_attr__( 'Probar conexión', 'dokan-dhl-per-vendor' ); ?>"
                            >
                                <?php esc_html_e( 'Probar conexión', 'dokan-dhl-per-vendor' ); ?>
                            </button>
                            <div class="dokan-dhl-test-notice dokan-alert" role="status" aria-live="polite" hidden="hidden"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>

        <div class="dokan-form-group">
            <div class="dokan-w3"></div>
            <div class="dokan-w6 dokan-text-left">
                <button class="dokan-btn dokan-btn-theme" type="submit">
                    <?php esc_html_e( 'Save Changes', 'dokan-dhl-per-vendor' ); ?>
                </button>
            </div>
        </div>
    </form>
</div>
