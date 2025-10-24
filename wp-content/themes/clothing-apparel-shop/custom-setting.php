<?php 

function clothing_apparel_shop_add_admin_menu() {
    add_menu_page(
        'Theme Settings', // Page title
        'Theme Settings', // Menu title
        'manage_options', // Capability
        'clothing-apparel-shop-theme-settings', // Menu slug
        'clothing_apparel_shop_settings_page' // Function to display the page
    );
}
add_action( 'admin_menu', 'clothing_apparel_shop_add_admin_menu' );

function clothing_apparel_shop_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Theme Settings', 'clothing-apparel-shop' ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'clothing_apparel_shop_settings_group' );
            do_settings_sections( 'clothing-apparel-shop-theme-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function clothing_apparel_shop_register_settings() {
    register_setting( 'clothing_apparel_shop_settings_group', 'clothing_apparel_shop_enable_animations' );

    add_settings_section(
        'clothing_apparel_shop_settings_section',
        __( 'Animation Settings', 'clothing-apparel-shop' ),
        null,
        'clothing-apparel-shop-theme-settings'
    );

    add_settings_field(
        'clothing_apparel_shop_enable_animations',
        __( 'Enable Animations', 'clothing-apparel-shop' ),
        'clothing_apparel_shop_enable_animations_callback',
        'clothing-apparel-shop-theme-settings',
        'clothing_apparel_shop_settings_section'
    );
}
add_action( 'admin_init', 'clothing_apparel_shop_register_settings' );

function clothing_apparel_shop_enable_animations_callback() {
    $checked = get_option( 'clothing_apparel_shop_enable_animations', true );
    ?>
    <input type="checkbox" name="clothing_apparel_shop_enable_animations" value="1" <?php checked( 1, $checked ); ?> />
    <?php
}

