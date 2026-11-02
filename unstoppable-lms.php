<?php
/**
 * Plugin Name: Unstoppable LMS
 * Plugin URI: https://unstoppablesolutions.app/plugins/unstoppable-lms/
 * Description: A clean, reliable Learning Management System for WordPress. Create courses, track progress, and manage students — without the bloat. Upgrade to Pro for gradebook, discussions, cohorts, and more.
 * Version: 1.6.5
 * Author: Unstoppable Solutions
 * Author URI: https://unstoppablesolutions.app
 * Text Domain: unstoppable-lms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Unstoppable_LMS
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin version - update this with each release.
define( 'UNSTOPPABLE_LMS_VERSION', '1.6.5' );

// Plugin slug.
define( 'UNSTOPPABLE_LMS_SLUG', 'unstoppable-lms' );

// Free version flag - Pro will override this.
if ( ! defined( 'UNSTOPPABLE_LMS_PRO' ) ) {
    define( 'UNSTOPPABLE_LMS_PRO', false );
}

// Plugin paths - with safety checks for PHP 8.1+
$unstoppable_lms_file = __FILE__;
$unstoppable_lms_dir = function_exists( 'plugin_dir_path' ) ? plugin_dir_path( $unstoppable_lms_file ) : dirname( $unstoppable_lms_file ) . '/';
$unstoppable_lms_url = function_exists( 'plugin_dir_url' ) ? plugin_dir_url( $unstoppable_lms_file ) : '';
$unstoppable_lms_basename = function_exists( 'plugin_basename' ) ? plugin_basename( $unstoppable_lms_file ) : basename( dirname( $unstoppable_lms_file ) ) . '/' . basename( $unstoppable_lms_file );

define( 'UNSTOPPABLE_LMS_PLUGIN_FILE', $unstoppable_lms_file );
define( 'UNSTOPPABLE_LMS_PLUGIN_DIR', $unstoppable_lms_dir );
define( 'UNSTOPPABLE_LMS_PLUGIN_URL', $unstoppable_lms_url );
define( 'UNSTOPPABLE_LMS_PLUGIN_BASENAME', $unstoppable_lms_basename );

// Minimum requirements.
define( 'UNSTOPPABLE_LMS_MIN_WP_VERSION', '5.8' );
define( 'UNSTOPPABLE_LMS_MIN_PHP_VERSION', '7.4' );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
final class Unstoppable_LMS {

    /**
     * Single instance of the class.
     *
     * @var Unstoppable_LMS
     */
    private static $instance = null;

    /**
     * Get the single instance of the class.
     *
     * @since 1.0.0
     * @return Unstoppable_LMS
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check minimum requirements.
     *
     * @since 1.0.0
     */
    private function check_requirements() {
        if ( version_compare( PHP_VERSION, UNSTOPPABLE_LMS_MIN_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }

        if ( version_compare( get_bloginfo( 'version' ), UNSTOPPABLE_LMS_MIN_WP_VERSION, '<' ) ) {
            add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
            return;
        }
    }

    /**
     * Include required files.
     *
     * @since 1.0.0
     */
    private function includes() {
        // Core classes.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/class-unstoppable-lms-install.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/class-unstoppable-lms-post-types.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/class-unstoppable-lms-cache.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/class-unstoppable-lms-settings.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/class-unstoppable-lms-limits.php';

        // Course functionality.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/course/class-unstoppable-lms-course.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/course/class-unstoppable-lms-lesson.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/course/class-unstoppable-lms-topic.php';

        // Progress tracking.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/progress/class-unstoppable-lms-progress-tracker.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/progress/class-unstoppable-lms-enrollment.php';

        // Quiz functionality (basic in free).
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/quiz/class-unstoppable-lms-quiz.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/quiz/class-unstoppable-lms-quiz-renderer.php';

        // Frontend.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/frontend/class-unstoppable-lms-templates.php';
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/frontend/class-unstoppable-lms-shortcodes.php';

        // Admin.
        if ( is_admin() ) {
            require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/admin/class-unstoppable-lms-admin.php';
            require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/admin/class-unstoppable-lms-admin-menus.php';
            require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/admin/class-unstoppable-lms-admin-notices.php';
        }

        // Student preview - needs to run on both admin and frontend.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/admin/class-unstoppable-lms-student-preview.php';

        // REST API.
        require_once UNSTOPPABLE_LMS_PLUGIN_DIR . 'includes/api/class-unstoppable-lms-rest-api.php';
    }

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Activation/deactivation.
        register_activation_hook( UNSTOPPABLE_LMS_PLUGIN_FILE, array( 'Unstoppable_LMS_Install', 'activate' ) );
        register_deactivation_hook( UNSTOPPABLE_LMS_PLUGIN_FILE, array( 'Unstoppable_LMS_Install', 'deactivate' ) );

        // Initialize plugin.
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Register REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Plugin action links on plugins page.
        add_filter( 'plugin_action_links_' . UNSTOPPABLE_LMS_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    /**
     * Add plugin action links on the plugins page.
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings' ) ),
            esc_html__( 'Settings', 'unstoppable-lms' )
        );

        $upgrade_link = sprintf(
            '<a href="%s" target="_blank" style="color: #f0b849; font-weight: bold;">%s</a>',
            esc_url( 'https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms' ),
            esc_html__( 'Go Pro', 'unstoppable-lms' )
        );

        array_unshift( $links, $settings_link );
        $links[] = $upgrade_link;

        return $links;
    }

    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     */
    public function init() {
        // Register post types.
        Unstoppable_LMS_Post_Types::register();

        // Initialize components.
        Unstoppable_LMS_Settings::init();
        Unstoppable_LMS_Limits::init();

        // Only init frontend templates on actual frontend requests, not AJAX or admin.
        if ( ! is_admin() && ! wp_doing_ajax() ) {
            Unstoppable_LMS_Templates::init();
        }

        Unstoppable_LMS_Shortcodes::init();

        // Student preview mode - runs on both admin and frontend.
        Unstoppable_LMS_Student_Preview::init();

        if ( is_admin() ) {
            Unstoppable_LMS_Admin::init();
            new Unstoppable_LMS_Admin_Notices();
        }

        /**
         * Fires after Unstoppable LMS is fully initialized.
         *
         * @since 1.0.0
         */
        do_action( 'unstoppable_lms_init' );
    }

    /**
     * Load plugin textdomain.
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'unstoppable-lms', false, dirname( UNSTOPPABLE_LMS_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_rest_routes() {
        $api = new Unstoppable_LMS_REST_API();
        $api->register_routes();
    }

    /**
     * PHP version notice.
     *
     * @since 1.0.0
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: 1: Required PHP version, 2: Current PHP version */
                    esc_html__( 'Unstoppable LMS requires PHP %1$s or higher. You are running PHP %2$s.', 'unstoppable-lms' ),
                    esc_html( UNSTOPPABLE_LMS_MIN_PHP_VERSION ),
                    esc_html( PHP_VERSION )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * WordPress version notice.
     *
     * @since 1.0.0
     */
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: 1: Required WordPress version, 2: Current WordPress version */
                    esc_html__( 'Unstoppable LMS requires WordPress %1$s or higher. You are running WordPress %2$s.', 'unstoppable-lms' ),
                    esc_html( UNSTOPPABLE_LMS_MIN_WP_VERSION ),
                    esc_html( get_bloginfo( 'version' ) )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Prevent cloning.
     *
     * @since 1.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        throw new Exception( esc_html__( 'Cannot unserialize singleton', 'unstoppable-lms' ) );
    }
}

/**
 * Returns the main instance of Unstoppable_LMS.
 *
 * @since 1.0.0
 * @return Unstoppable_LMS
 */
function unstoppable_lms() {
    return Unstoppable_LMS::instance();
}

// Initialize the plugin.
unstoppable_lms();
