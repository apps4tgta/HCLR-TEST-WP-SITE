<?php
/**
 * HCLR Well-Rooted Theme Functions
 *
 * @package HCLR\WellRooted
 * @version 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HCLR_THEME_VERSION', '1.0.0' );
define( 'HCLR_THEME_DIR', get_template_directory() );
define( 'HCLR_THEME_URI', get_template_directory_uri() );

// ── Include helpers ──────────────────────────────────────────────────────────
require_once HCLR_THEME_DIR . '/inc/template-functions.php';
require_once HCLR_THEME_DIR . '/inc/class-calendar-widget.php';

// ── Theme Setup ───────────────────────────────────────────────────────────────

/**
 * Theme setup: supports, image sizes, nav menus.
 */
function hclr_theme_setup(): void {
    // Text domain.
    load_theme_textdomain( 'hclr-well-rooted', HCLR_THEME_DIR . '/languages' );

    // Core supports.
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array( 'site-title', 'site-description' ),
    ) );

    // Custom header.
    add_theme_support( 'custom-header', array(
        'default-image'          => '',
        'default-text-color'     => 'ffffff',
        'width'                  => 1400,
        'height'                 => 500,
        'flex-width'             => true,
        'flex-height'            => true,
    ) );

    // Image sizes.
    add_image_size( 'hclr-hero',          1400, 600,  true );
    add_image_size( 'hclr-property-card', 600,  400,  true );
    add_image_size( 'hclr-gallery-thumb', 400,  300,  true );
    add_image_size( 'hclr-square',        500,  500,  true );

    // Nav menus.
    register_nav_menus( array(
        'primary'      => __( 'Primary Navigation', 'hclr-well-rooted' ),
        'footer'       => __( 'Footer Navigation',  'hclr-well-rooted' ),
        'footer-legal' => __( 'Footer Legal Links', 'hclr-well-rooted' ),
    ) );
}
add_action( 'after_setup_theme', 'hclr_theme_setup' );

// ── Widget Areas ─────────────────────────────────────────────────────────────

/**
 * Register sidebar widget areas.
 */
function hclr_register_sidebars(): void {
    $defaults = array(
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget__title">',
        'after_title'   => '</h4>',
    );

    register_sidebar( array_merge( $defaults, array(
        'name'        => __( 'Main Sidebar', 'hclr-well-rooted' ),
        'id'          => 'sidebar-main',
        'description' => __( 'Widgets added here appear in the blog sidebar.', 'hclr-well-rooted' ),
    ) ) );

    for ( $i = 1; $i <= 3; $i++ ) {
        register_sidebar( array_merge( $defaults, array(
            /* translators: %d: column number */
            'name'        => sprintf( __( 'Footer Column %d', 'hclr-well-rooted' ), $i ),
            'id'          => "footer-col-{$i}",
        ) ) );
    }
}
add_action( 'widgets_init', 'hclr_register_sidebars' );

// ── Scripts & Styles ─────────────────────────────────────────────────────────

/**
 * Enqueue theme assets.
 */
function hclr_enqueue_assets(): void {
    // Google Fonts.
    wp_enqueue_style(
        'hclr-google-fonts',
        'https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap',
        array(),
        null
    );

    // Swiper.js (for hero carousels).
    wp_enqueue_style(
        'swiper',
        'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.css',
        array(),
        '11.0.5'
    );
    wp_enqueue_script(
        'swiper',
        'https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js',
        array(),
        '11.0.5',
        true
    );

    // Theme main stylesheet.
    wp_enqueue_style(
        'hclr-theme',
        get_stylesheet_uri(),
        array( 'hclr-google-fonts' ),
        HCLR_THEME_VERSION
    );

    // Component CSS.
    wp_enqueue_style(
        'hclr-components',
        HCLR_THEME_URI . '/assets/css/main.css',
        array( 'hclr-theme' ),
        HCLR_THEME_VERSION
    );

    // Theme JS.
    wp_enqueue_script(
        'hclr-theme-js',
        HCLR_THEME_URI . '/assets/js/main.js',
        array( 'swiper' ),
        HCLR_THEME_VERSION,
        true
    );

    // Pass REST URL + nonce so the calendar widget and booking form can call the API.
    wp_localize_script( 'hclr-theme-js', 'hclr_theme', array(
        'ajax_url'    => admin_url( 'admin-ajax.php' ),
        'rest_url'    => rest_url(),
        'nonce'       => wp_create_nonce( 'wp_rest' ),
        'theme_url'   => HCLR_THEME_URI,
        'site_url'    => site_url(),
        'booking_url' => get_page_link( get_page_by_path( 'booking' ) ) ?: home_url( '/booking/' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'hclr_enqueue_assets' );

// ── Property ID Meta Box ──────────────────────────────────────────────────────

/**
 * Add property ID meta box to pages (when HCLR plugin is NOT active).
 * The plugin handles this when active; theme provides fallback.
 */
function hclr_theme_add_property_metabox(): void {
    if ( class_exists( '\HCLR\DirectBooking\Plugin' ) ) {
        return; // Plugin handles it.
    }

    add_meta_box(
        'hclr_property_id_theme',
        __( 'OwnerRez Property ID', 'hclr-well-rooted' ),
        'hclr_theme_render_property_metabox',
        'page',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'hclr_theme_add_property_metabox' );

/**
 * Render property meta box.
 *
 * @param WP_Post $post Current post.
 */
function hclr_theme_render_property_metabox( WP_Post $post ): void {
    wp_nonce_field( 'hclr_theme_property_nonce', '_hclr_theme_nonce' );
    $value = get_post_meta( $post->ID, '_hclr_property_id', true );
    ?>
    <input type="number" name="_hclr_property_id_theme" style="width:100%"
           value="<?php echo esc_attr( $value ); ?>" min="1"
           placeholder="e.g. 483733" />
    <p style="font-size:11px;color:#888;margin-top:6px;">
        <?php esc_html_e( 'Numeric ID from your OwnerRez account.', 'hclr-well-rooted' ); ?>
    </p>
    <?php
}

/**
 * Save property meta box.
 *
 * @param int $post_id Post ID.
 */
function hclr_theme_save_property_metabox( int $post_id ): void {
    if ( ! isset( $_POST['_hclr_theme_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_hclr_theme_nonce'] ) ), 'hclr_theme_property_nonce' )
        || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['_hclr_property_id_theme'] ) ) {
        update_post_meta( $post_id, '_hclr_property_id', absint( $_POST['_hclr_property_id_theme'] ) );
    }
}
add_action( 'save_post_page', 'hclr_theme_save_property_metabox' );

// ── Excerpt ───────────────────────────────────────────────────────────────────

add_filter( 'excerpt_length', fn() => 20, 999 );
add_filter( 'excerpt_more',   fn() => '…' );

// ── WP Body Open ─────────────────────────────────────────────────────────────

if ( ! function_exists( 'wp_body_open' ) ) {
    function wp_body_open(): void {
        do_action( 'wp_body_open' );
    }
}

// ── Custom Login Logo ─────────────────────────────────────────────────────────

add_action( 'login_enqueue_scripts', function () {
    $logo = get_theme_mod( 'custom_logo' );
    if ( ! $logo ) return;
    $logo_url = wp_get_attachment_image_url( $logo, 'full' );
    if ( ! $logo_url ) return;
    ?>
    <style>
        #login h1 a {
            background-image: url('<?php echo esc_url( $logo_url ); ?>');
            background-size: contain; width: 200px; height: 60px;
        }
    </style>
    <?php
} );
