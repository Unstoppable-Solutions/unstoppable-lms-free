<?php
/**
 * Student Preview Mode
 *
 * Allows admins/instructors to preview courses as students see them.
 *
 * @package Unstoppable_LMS
 * @since 1.6.4
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Student Preview Class.
 */
class Unstoppable_LMS_Student_Preview {

    /**
     * Session key for preview mode.
     *
     * @var string
     */
    const PREVIEW_KEY = 'unstoppable_lms_student_preview';

    /**
     * Initialize.
     */
    public static function init() {
        // Add preview button to course edit screen.
        add_action( 'edit_form_after_title', array( __CLASS__, 'add_preview_button' ) );
        
        // Handle preview mode toggle.
        add_action( 'init', array( __CLASS__, 'handle_preview_toggle' ) );
        
        // Show preview bar on frontend.
        add_action( 'wp_body_open', array( __CLASS__, 'show_preview_bar' ) );
        add_action( 'wp_footer', array( __CLASS__, 'show_preview_bar_fallback' ) );
        
        // Enqueue preview bar styles.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_preview_styles' ) );
        
        // Filter user capabilities when in preview mode.
        add_filter( 'user_has_cap', array( __CLASS__, 'filter_capabilities' ), 100, 4 );
    }

    /**
     * Check if current user can use preview mode.
     *
     * @return bool
     */
    public static function can_preview() {
        return current_user_can( 'edit_lms_courses' );
    }

    /**
     * Check if currently in preview mode.
     *
     * @return bool
     */
    public static function is_previewing() {
        if ( ! self::can_preview() ) {
            return false;
        }
        
        if ( isset( $_SESSION[ self::PREVIEW_KEY ] ) && $_SESSION[ self::PREVIEW_KEY ] ) {
            return true;
        }
        
        // Fallback to transient if sessions not working.
        $user_id = get_current_user_id();
        if ( $user_id && get_transient( self::PREVIEW_KEY . '_' . $user_id ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Start preview mode.
     */
    public static function start_preview() {
        if ( ! session_id() ) {
            @session_start();
        }
        $_SESSION[ self::PREVIEW_KEY ] = true;
        
        // Fallback transient.
        $user_id = get_current_user_id();
        if ( $user_id ) {
            set_transient( self::PREVIEW_KEY . '_' . $user_id, true, HOUR_IN_SECONDS );
        }
    }

    /**
     * Stop preview mode.
     */
    public static function stop_preview() {
        if ( ! session_id() ) {
            @session_start();
        }
        unset( $_SESSION[ self::PREVIEW_KEY ] );
        
        // Clear fallback transient.
        $user_id = get_current_user_id();
        if ( $user_id ) {
            delete_transient( self::PREVIEW_KEY . '_' . $user_id );
        }
    }

    /**
     * Handle preview toggle from URL.
     */
    public static function handle_preview_toggle() {
        if ( ! self::can_preview() ) {
            return;
        }

        // Start preview.
        if ( isset( $_GET['lms_preview'] ) && $_GET['lms_preview'] === 'start' ) {
            if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'lms_preview_start' ) ) {
                return;
            }
            self::start_preview();
            
            // Redirect to course.
            $course_id = intval( $_GET['course_id'] ?? 0 );
            if ( $course_id ) {
                wp_safe_redirect( get_permalink( $course_id ) );
                exit;
            }
        }

        // Stop preview.
        if ( isset( $_GET['lms_preview'] ) && $_GET['lms_preview'] === 'stop' ) {
            if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'lms_preview_stop' ) ) {
                return;
            }
            self::stop_preview();
            
            // Redirect back.
            $redirect = remove_query_arg( array( 'lms_preview', '_wpnonce' ) );
            wp_safe_redirect( $redirect );
            exit;
        }
    }

    /**
     * Add preview button to course edit screen.
     *
     * @param WP_Post $post Current post.
     */
    public static function add_preview_button( $post ) {
        if ( ! $post || $post->post_type !== 'lms_course' ) {
            return;
        }

        if ( $post->post_status !== 'publish' ) {
            return;
        }

        $preview_url = add_query_arg( array(
            'lms_preview' => 'start',
            'course_id'   => $post->ID,
            '_wpnonce'    => wp_create_nonce( 'lms_preview_start' ),
        ), admin_url() );

        ?>
        <div style="margin: 15px 0;">
            <a href="<?php echo esc_url( $preview_url ); ?>" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-visibility" style="font-size: 18px; width: 18px; height: 18px;"></span>
                <?php esc_html_e( 'Preview as Student', 'unstoppable-lms' ); ?>
            </a>
            <span class="description" style="margin-left: 10px;">
                <?php esc_html_e( 'See this course exactly as students see it.', 'unstoppable-lms' ); ?>
            </span>
        </div>
        <?php
    }

    /**
     * Show preview bar on frontend.
     */
    public static function show_preview_bar() {
        if ( ! self::is_previewing() ) {
            return;
        }

        if ( is_admin() ) {
            return;
        }

        $exit_url = add_query_arg( array(
            'lms_preview' => 'stop',
            '_wpnonce'    => wp_create_nonce( 'lms_preview_stop' ),
        ) );

        ?>
        <div id="lms-preview-bar" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999999;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        ">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-visibility" style="font-size: 20px;"></span>
                <strong><?php esc_html_e( 'Student Preview Mode', 'unstoppable-lms' ); ?></strong>
                <span style="opacity: 0.9;">—</span>
                <span style="opacity: 0.9;"><?php esc_html_e( 'You are viewing this course as a student would see it.', 'unstoppable-lms' ); ?></span>
            </div>
            <a href="<?php echo esc_url( $exit_url ); ?>" style="
                background: rgba(255,255,255,0.2);
                color: #fff;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                <span class="dashicons dashicons-no-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <?php esc_html_e( 'Exit Preview', 'unstoppable-lms' ); ?>
            </a>
        </div>
        <div style="height: 50px;"></div><!-- Spacer for fixed bar -->
        <?php
    }

    /**
     * Fallback for themes without wp_body_open.
     */
    public static function show_preview_bar_fallback() {
        if ( ! self::is_previewing() ) {
            return;
        }

        if ( is_admin() ) {
            return;
        }

        // Check if wp_body_open ran (it sets a flag).
        if ( did_action( 'wp_body_open' ) > 0 ) {
            return; // Already rendered via wp_body_open.
        }

        // Render with JS for themes without wp_body_open.
        $exit_url = add_query_arg( array(
            'lms_preview' => 'stop',
            '_wpnonce'    => wp_create_nonce( 'lms_preview_stop' ),
        ) );

        ?>
        <script>
        (function() {
            if (document.getElementById('lms-preview-bar')) return;
            
            var bar = document.createElement('div');
            bar.id = 'lms-preview-bar';
            bar.innerHTML = <?php echo wp_json_encode(
                '<div style="position:fixed;top:0;left:0;right:0;z-index:999999;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;font-size:14px;box-shadow:0 2px 10px rgba(0,0,0,0.2);">' .
                    '<div style="display:flex;align-items:center;gap:10px;"><strong>' . esc_html__( 'Student Preview Mode', 'unstoppable-lms' ) . '</strong><span style="opacity:0.9;">— ' . esc_html__( 'Viewing as student', 'unstoppable-lms' ) . '</span></div>' .
                    '<a href="' . esc_url( $exit_url ) . '" style="background:rgba(255,255,255,0.2);color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-weight:500;">' . esc_html__( 'Exit Preview', 'unstoppable-lms' ) . '</a>' .
                '</div>'
            ); ?>;
            document.body.insertBefore(bar, document.body.firstChild);
            document.body.style.paddingTop = '50px';
        })();
        </script>
        <?php
    }

    /**
     * Enqueue preview styles.
     */
    public static function enqueue_preview_styles() {
        if ( ! self::is_previewing() ) {
            return;
        }

        // Load dashicons on frontend for preview bar.
        wp_enqueue_style( 'dashicons' );
    }

    /**
     * Filter capabilities when in preview mode.
     *
     * When previewing, admins should NOT see admin-only elements
     * but should still be logged in.
     *
     * @param array   $allcaps All capabilities.
     * @param array   $caps    Required capabilities.
     * @param array   $args    Arguments.
     * @param WP_User $user    User object.
     * @return array Modified capabilities.
     */
    public static function filter_capabilities( $allcaps, $caps, $args, $user ) {
        if ( ! self::is_previewing() ) {
            return $allcaps;
        }

        // In preview mode, remove LMS admin caps so UI shows student view.
        $admin_caps = array(
            'edit_lms_courses',
            'delete_lms_courses',
            'edit_others_lms_courses',
            'manage_lms_settings',
            'view_lms_reports',
            'manage_lms_students',
        );

        foreach ( $admin_caps as $cap ) {
            if ( isset( $allcaps[ $cap ] ) ) {
                $allcaps[ $cap ] = false;
            }
        }

        return $allcaps;
    }
}
