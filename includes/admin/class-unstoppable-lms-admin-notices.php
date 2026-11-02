<?php
/**
 * Admin Notices Class
 *
 * Handles all admin dashboard notices for the plugin.
 *
 * @package Unstoppable_LMS
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Notices Class.
 */
class Unstoppable_LMS_Admin_Notices {

    /**
     * Plugin prefix.
     *
     * @var string
     */
    private $prefix = 'unstoppable_lms';

    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name = 'Unstoppable LMS';

    /**
     * Customer dashboard URL.
     *
     * @var string
     */
    private $dashboard_url = 'https://unstoppablesolutions.app/Dashboard';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
        add_action( 'admin_notices', array( $this, 'display_flash_notices' ) );
        add_action( 'wp_ajax_' . $this->prefix . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue admin scripts for notice dismissal.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only enqueue on LMS pages or dashboard.
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $is_lms_page = strpos( $screen->id, 'unstoppable-lms' ) !== false ||
                       strpos( $screen->id, 'lms_' ) !== false ||
                       $screen->id === 'dashboard';

        if ( ! $is_lms_page ) {
            return;
        }

        wp_add_inline_script( 'jquery', $this->get_dismiss_script() );
    }

    /**
     * Get dismiss notice JavaScript.
     *
     * @return string JavaScript code.
     */
    private function get_dismiss_script() {
        return "
        jQuery(document).ready(function($) {
            $(document).on('click', '.unstoppable-lms-notice .notice-dismiss, .unstoppable-lms-dismiss-notice', function(e) {
                var \$notice = $(this).closest('.unstoppable-lms-notice');
                var notice = \$notice.data('notice');
                var announcementId = \$notice.data('announcement-id') || '';
                
                if (notice) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'unstoppable_lms_dismiss_notice',
                            notice: notice,
                            announcement_id: announcementId,
                            nonce: '" . wp_create_nonce( $this->prefix . '_dismiss_notice' ) . "'
                        }
                    });
                }
                
                if ($(this).hasClass('unstoppable-lms-dismiss-notice')) {
                    e.preventDefault();
                    \$notice.fadeOut();
                }
            });
        });
        ";
    }

    /**
     * Display all active notices.
     */
    public function display_notices() {
        // Welcome/Setup notice (shows once after activation).
        $this->maybe_show_welcome_notice();

        // License activation reminder.
        $this->maybe_show_license_notice();

        // Review request (shows after 7 days of use).
        $this->maybe_show_review_notice();

        // Important announcements.
        $this->maybe_show_announcement_notice();
    }

    /**
     * Welcome/Setup notice after plugin activation.
     */
    private function maybe_show_welcome_notice() {
        // Check if already dismissed.
        if ( get_option( $this->prefix . '_welcome_notice_dismissed' ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on dashboard and plugin pages.
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $allowed_screens = array( 'dashboard', 'plugins' );
        $is_lms_page     = strpos( $screen->id, 'unstoppable-lms' ) !== false;

        if ( ! $is_lms_page && ! in_array( $screen->id, $allowed_screens, true ) ) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible unstoppable-lms-notice" data-notice="welcome">
            <p>
                <strong><?php echo esc_html( $this->plugin_name ); ?></strong> —
                <?php esc_html_e( 'Thanks for installing! Get started by creating your first course.', 'unstoppable-lms' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-courses' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Create Course', 'unstoppable-lms' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * License activation reminder.
     */
    private function maybe_show_license_notice() {
        // License notice is Pro-only - skip in free version.
        if ( ! defined( 'UNSTOPPABLE_LMS_PRO' ) || ! UNSTOPPABLE_LMS_PRO ) {
            return;
        }
        if ( ! class_exists( 'Unstoppable_LMS_License' ) ) {
            return;
        }

        // Check if license is already active.
        $license = Unstoppable_LMS_License::get_instance();
        if ( $license->is_active() ) {
            return;
        }

        // Check if dismissed recently (re-show after 7 days).
        $dismissed_date = get_option( $this->prefix . '_license_notice_dismissed_date' );
        if ( $dismissed_date && time() < ( $dismissed_date + ( 7 * DAY_IN_SECONDS ) ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on LMS pages.
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'unstoppable-lms' ) === false ) {
            return;
        }

        ?>
        <div class="notice notice-warning is-dismissible unstoppable-lms-notice" data-notice="license">
            <p>
                <strong><?php echo esc_html( $this->plugin_name ); ?></strong> —
                <?php esc_html_e( 'Please activate your license to receive updates and support.', 'unstoppable-lms' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=license' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Activate License', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( $this->dashboard_url ); ?>" target="_blank" class="button" style="margin-left: 5px;">
                    <?php esc_html_e( 'Get License Key', 'unstoppable-lms' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Review request notice after 7 days.
     */
    private function maybe_show_review_notice() {
        // Check if already dismissed.
        if ( get_option( $this->prefix . '_review_notice_dismissed' ) ) {
            return;
        }

        // Check install date.
        $install_date = get_option( $this->prefix . '_install_date' );
        if ( ! $install_date ) {
            return;
        }

        // Show after 7 days.
        if ( time() < ( $install_date + ( 7 * DAY_IN_SECONDS ) ) ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Only show on plugin pages.
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'unstoppable-lms' ) === false ) {
            return;
        }

        ?>
        <div class="notice notice-info is-dismissible unstoppable-lms-notice" data-notice="review">
            <p>
                <?php
                printf(
                    /* translators: %s: Plugin name */
                    esc_html__( 'Enjoying %s? Please leave a review to help others discover the plugin!', 'unstoppable-lms' ),
                    '<strong>' . esc_html( $this->plugin_name ) . '</strong>'
                );
                ?>
                <a href="https://wordpress.org/support/plugin/unstoppable-lms/reviews/#new-post" target="_blank" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Leave a Review', 'unstoppable-lms' ); ?>
                </a>
                <button type="button" class="button unstoppable-lms-dismiss-notice" style="margin-left: 5px;">
                    <?php esc_html_e( 'Maybe Later', 'unstoppable-lms' ); ?>
                </button>
            </p>
        </div>
        <?php
    }

    /**
     * Important announcements (security updates, breaking changes, etc.).
     */
    private function maybe_show_announcement_notice() {
        // Get announcement from options (can be set via remote API or manually).
        $announcement = get_option( $this->prefix . '_announcement' );

        if ( empty( $announcement ) || empty( $announcement['message'] ) ) {
            return;
        }

        // Check if this announcement was dismissed.
        $dismissed_id = get_option( $this->prefix . '_announcement_dismissed_id' );
        if ( ! empty( $announcement['id'] ) && $dismissed_id === $announcement['id'] ) {
            return;
        }

        // Only show to admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $type          = isset( $announcement['type'] ) ? $announcement['type'] : 'info';
        $allowed_types = array( 'info', 'warning', 'error', 'success' );
        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'info';
        }

        ?>
        <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible unstoppable-lms-notice" data-notice="announcement" data-announcement-id="<?php echo esc_attr( $announcement['id'] ?? '' ); ?>">
            <p>
                <strong><?php echo esc_html( $this->plugin_name ); ?></strong> —
                <?php echo wp_kses_post( $announcement['message'] ); ?>
                <?php if ( ! empty( $announcement['link'] ) && ! empty( $announcement['link_text'] ) ) : ?>
                    <a href="<?php echo esc_url( $announcement['link'] ); ?>" target="_blank" class="button" style="margin-left: 10px;">
                        <?php echo esc_html( $announcement['link_text'] ); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler to dismiss notices.
     */
    public function ajax_dismiss_notice() {
        // Verify nonce.
        if ( ! check_ajax_referer( $this->prefix . '_dismiss_notice', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'unstoppable-lms' ) ) );
        }

        // Verify permissions.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-lms' ) ) );
        }

        // Sanitize and validate notice type.
        $notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';

        $allowed_notices = array( 'welcome', 'setup', 'review', 'license', 'announcement' );
        if ( empty( $notice ) || ! in_array( $notice, $allowed_notices, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid notice type.', 'unstoppable-lms' ) ) );
        }

        // Save dismissal.
        update_option( $this->prefix . '_' . $notice . '_notice_dismissed', true );

        // For notices that re-show after a period, save the date.
        if ( 'license' === $notice ) {
            update_option( $this->prefix . '_' . $notice . '_notice_dismissed_date', time() );
        }

        // For announcements, save the specific announcement ID.
        if ( 'announcement' === $notice ) {
            $announcement_id = isset( $_POST['announcement_id'] ) ? sanitize_key( $_POST['announcement_id'] ) : '';
            if ( $announcement_id ) {
                update_option( $this->prefix . '_announcement_dismissed_id', $announcement_id );
            }
        }

        wp_send_json_success();
    }

    /**
     * Display flash notices (success/error messages).
     * These are one-time notices triggered by user actions.
     */
    public function display_flash_notices() {
        $notices = get_transient( $this->prefix . '_flash_notices' );

        if ( empty( $notices ) || ! is_array( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            $type    = isset( $notice['type'] ) ? $notice['type'] : 'info';
            $message = isset( $notice['message'] ) ? $notice['message'] : '';

            if ( empty( $message ) ) {
                continue;
            }

            $allowed_types = array( 'success', 'error', 'warning', 'info' );
            if ( ! in_array( $type, $allowed_types, true ) ) {
                $type = 'info';
            }

            ?>
            <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
                <p><?php echo wp_kses_post( $message ); ?></p>
            </div>
            <?php
        }

        // Clear flash notices after displaying.
        delete_transient( $this->prefix . '_flash_notices' );
    }

    /**
     * Add a flash notice to be displayed on next page load.
     *
     * @param string $message The notice message.
     * @param string $type    Notice type: success, error, warning, info.
     */
    public static function add_flash_notice( $message, $type = 'success' ) {
        $prefix  = 'unstoppable_lms';
        $notices = get_transient( $prefix . '_flash_notices' );

        if ( ! is_array( $notices ) ) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient( $prefix . '_flash_notices', $notices, 60 );
    }
}
