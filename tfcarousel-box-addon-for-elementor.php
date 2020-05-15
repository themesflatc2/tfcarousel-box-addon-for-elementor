<?php
/*
Plugin Name: TFCarousel Box Addon For Elementor
Description: The theme's components
Author: corpthemes
Author URI: http://corpthemes.com//plugin/tfcarousel-box-addon-for-elementor.zip
Version: 1.0.0
Text Domain: tfcarousel-box-addon-for-elementor
Domain Path: /languages
*/

if (!defined('ABSPATH'))
    exit;

final class TFCarousel_Addon_Elementor {

    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '2.0.0';
    const MINIMUM_PHP_VERSION = '5.2';

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'init', [ $this, 'i18n' ] );
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'widget_styles' ] , 100 );
        add_action( 'admin_enqueue_scripts', [ $this, 'widget_styles' ] , 100 );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ], 100 );
    }

    public function i18n() {
        load_plugin_textdomain( 'tfcarousel-box-addon-for-elementor', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    public function init() {
        // Check if Elementor installed and activated        
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'tf_admin_notice_missing_main_plugin' ] );
            return;
        }

        // Check for required Elementor version
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
            return;
        }

        // Check for required PHP version
        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
            return;
        }

        // Add Plugin actions
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
        add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );

        add_action( 'elementor/elements/categories_registered', function () {
            $elementsManager = \Elementor\Plugin::instance()->elements_manager;
            $elementsManager->add_category(
                'suri_addons',
                array(
                  'title' => 'SURI ADDONS',
                  'icon'  => 'fonts',
            ));
        });
    }    

    public function tf_admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'tfcarousel-box-addon-for-elementor' ),
            '<strong>' . esc_html__( 'TFCarousel Box Addon For Elementor', 'tfcarousel-box-addon-for-elementor' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'tfcarousel-box-addon-for-elementor' ) . '</strong>'
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    public function admin_notice_minimum_elementor_version() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'tfcarousel-box-addon-for-elementor' ),
            '<strong>' . esc_html__( 'TFCarousel Box Addon For Elementor', 'tfcarousel-box-addon-for-elementor' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'tfcarousel-box-addon-for-elementor' ) . '</strong>',
             self::MINIMUM_ELEMENTOR_VERSION
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

    }

    public function admin_notice_minimum_php_version() {

        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'tfcarousel-box-addon-for-elementor' ),
            '<strong>' . esc_html__( 'TFCarousel Box Addon For Elementor', 'tfcarousel-box-addon-for-elementor' ) . '</strong>',
            '<strong>' . esc_html__( 'PHP', 'tfcarousel-box-addon-for-elementor' ) . '</strong>',
             self::MINIMUM_PHP_VERSION
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

    }

    public function init_widgets() {
        require_once( __DIR__ . '/widgets/widget-carousel.php' );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \TFCarousel_Widget() );
    }

    public function init_controls() {}    

    public function widget_styles() {
        wp_register_style('regular', ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/regular.min.css', __FILE__);
        wp_enqueue_style( 'regular' );
        wp_register_style( 'owl-carousel', plugins_url( '/assets/css/owl.carousel.css', __FILE__ ) );
        wp_enqueue_style( 'owl-carousel' ); 
        wp_register_style( 'tf-carosel-style', plugins_url( '/assets/css/tf-style.css', __FILE__ ) );
        wp_enqueue_style( 'tf-carosel-style' );        
    }

    public function widget_scripts() {
        wp_enqueue_script('jquery');
        wp_register_script( 'owl-carousel', plugins_url( '/assets/js/owl.carousel.min.js', __FILE__ ), [ 'jquery' ], false, true );
        wp_register_script( 'tf-carousel-main', plugins_url( '/assets/js/tf-main.js', __FILE__ ), [ 'jquery' ], false, true );
        wp_enqueue_script( 'tf-carousel-main' );
    }

    static function tf_get_template_elementor($type = null) {
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
        ];
        if ($type) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'elementor_library_type',
                    'field' => 'slug',
                    'terms' => $type,
                ],
            ];
        }
        $template = get_posts($args);
        $tpl = array();
        if (!empty($template) && !is_wp_error($template)) {
            foreach ($template as $post) {
                $tpl[$post->ID] = $post->post_title;
            }
        }
        return $tpl;
    }  

}
TFCarousel_Addon_Elementor::instance();