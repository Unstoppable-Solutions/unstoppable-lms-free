<?php
/**
 * Free Version Limits
 *
 * Enforces limits on the free version of Unstoppable LMS.
 * Pro version removes all limits.
 *
 * @package Unstoppable_LMS
 * @since 1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Limits class for free version.
 */
class Unstoppable_LMS_Limits {

    /**
     * Maximum courses allowed in free version.
     */
    const MAX_COURSES = 1;

    /**
     * Maximum lessons per course in free version.
     */
    const MAX_LESSONS_PER_COURSE = 5;

    /**
     * Initialize limits.
     */
    public static function init() {
        // Block course creation beyond limit.
        add_filter( 'wp_insert_post_data', array( __CLASS__, 'check_course_limit' ), 10, 2 );

        // Block lesson creation beyond limit.
        add_filter( 'wp_insert_post_data', array( __CLASS__, 'check_lesson_limit' ), 10, 2 );

        // Add admin notices for limits.
        add_action( 'admin_notices', array( __CLASS__, 'limit_notices' ) );

        // Modify "Add New" buttons when at limit.
        add_action( 'admin_footer', array( __CLASS__, 'maybe_disable_add_buttons' ) );
    }

    /**
     * Check if Pro version is active.
     *
     * @return bool
     */
    public static function is_pro() {
        // Pro version will define this constant.
        return defined( 'UNSTOPPABLE_LMS_PRO' ) && UNSTOPPABLE_LMS_PRO;
    }

    /**
     * Get current course count.
     *
     * @return int
     */
    public static function get_course_count() {
        $count = wp_count_posts( 'lms_course' );
        return isset( $count->publish ) ? (int) $count->publish : 0;
    }

    /**
     * Get lesson count for a course.
     *
     * @param int $course_id Course ID.
     * @return int
     */
    public static function get_lesson_count( $course_id ) {
        $lessons = get_posts( array(
            'post_type'      => 'lms_lesson',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_lms_course_id',
                    'value' => $course_id,
                ),
            ),
            'fields'         => 'ids',
        ) );

        return count( $lessons );
    }

    /**
     * Check if user can create more courses.
     *
     * @return bool
     */
    public static function can_create_course() {
        if ( self::is_pro() ) {
            return true;
        }

        return self::get_course_count() < self::MAX_COURSES;
    }

    /**
     * Check if user can create more lessons in a course.
     *
     * @param int $course_id Course ID.
     * @return bool
     */
    public static function can_create_lesson( $course_id ) {
        if ( self::is_pro() ) {
            return true;
        }

        return self::get_lesson_count( $course_id ) < self::MAX_LESSONS_PER_COURSE;
    }

    /**
     * Block course creation if at limit.
     *
     * @param array $data    Post data.
     * @param array $postarr Post array.
     * @return array
     */
    public static function check_course_limit( $data, $postarr ) {
        if ( self::is_pro() ) {
            return $data;
        }

        // Only check for new courses being published.
        if ( 'lms_course' !== $data['post_type'] ) {
            return $data;
        }

        // Allow updates to existing courses.
        if ( ! empty( $postarr['ID'] ) ) {
            $existing = get_post( $postarr['ID'] );
            if ( $existing && 'lms_course' === $existing->post_type ) {
                return $data;
            }
        }

        // Check limit for new courses.
        if ( 'publish' === $data['post_status'] && ! self::can_create_course() ) {
            // Change to draft and set flag.
            $data['post_status'] = 'draft';
            set_transient( 'unstoppable_lms_course_limit_hit', true, 30 );
        }

        return $data;
    }

    /**
     * Block lesson creation if at limit.
     *
     * @param array $data    Post data.
     * @param array $postarr Post array.
     * @return array
     */
    public static function check_lesson_limit( $data, $postarr ) {
        if ( self::is_pro() ) {
            return $data;
        }

        // Only check for new lessons being published.
        if ( 'lms_lesson' !== $data['post_type'] ) {
            return $data;
        }

        // Allow updates to existing lessons.
        if ( ! empty( $postarr['ID'] ) ) {
            $existing = get_post( $postarr['ID'] );
            if ( $existing && 'lms_lesson' === $existing->post_type ) {
                return $data;
            }
        }

        // Get course ID from meta.
        $course_id = 0;
        if ( ! empty( $postarr['meta_input']['_lms_course_id'] ) ) {
            $course_id = absint( $postarr['meta_input']['_lms_course_id'] );
        }

        // Check limit for new lessons.
        if ( $course_id && 'publish' === $data['post_status'] && ! self::can_create_lesson( $course_id ) ) {
            $data['post_status'] = 'draft';
            set_transient( 'unstoppable_lms_lesson_limit_hit', true, 30 );
        }

        return $data;
    }

    /**
     * Display limit notices.
     */
    public static function limit_notices() {
        if ( self::is_pro() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Course limit hit.
        if ( get_transient( 'unstoppable_lms_course_limit_hit' ) ) {
            delete_transient( 'unstoppable_lms_course_limit_hit' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'Ready to Scale?', 'unstoppable-lms' ); ?></strong> —
                    <?php
                    printf(
                        /* translators: %s: upgrade link */
                        esc_html__( 'The free version is designed for single-course sites. %s for unlimited courses, gradebook, discussions, and more.', 'unstoppable-lms' ),
                        '<a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank"><strong>' . esc_html__( 'Upgrade to Pro for just $29.99/year', 'unstoppable-lms' ) . '</strong></a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // Lesson limit hit.
        if ( get_transient( 'unstoppable_lms_lesson_limit_hit' ) ) {
            delete_transient( 'unstoppable_lms_lesson_limit_hit' );
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'Need More Lessons?', 'unstoppable-lms' ); ?></strong> —
                    <?php
                    printf(
                        /* translators: %s: upgrade link */
                        esc_html__( 'The free version supports 5-lesson mini-courses. %s for unlimited lessons plus gradebook, discussions, and cohorts.', 'unstoppable-lms' ),
                        '<a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank"><strong>' . esc_html__( 'Upgrade to Pro for just $29.99/year', 'unstoppable-lms' ) . '</strong></a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // Show info on LMS pages.
        if ( strpos( $screen->id, 'unstoppable-lms' ) !== false || strpos( $screen->id, 'lms_' ) !== false ) {
            $course_count = self::get_course_count();
            $at_course_limit = $course_count >= self::MAX_COURSES;

            if ( $at_course_limit ) {
                ?>
                <div class="notice notice-info">
                    <p>
                        <span class="dashicons dashicons-info" style="color: #72aee6;"></span>
                        <?php esc_html_e( 'You\'re using the free version, designed for single-course sites and pilots.', 'unstoppable-lms' ); ?>
                        <a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank">
                            <?php esc_html_e( 'Ready to scale? Get Pro for unlimited courses, gradebook, discussions & more — just $29.99/year', 'unstoppable-lms' ); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Disable add buttons when at limit.
     */
    public static function maybe_disable_add_buttons() {
        if ( self::is_pro() ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Disable "Add Course" if at limit.
        if ( ! self::can_create_course() && ( $screen->id === 'edit-lms_course' || strpos( $screen->id, 'unstoppable-lms' ) !== false ) ) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Disable WordPress add new buttons
                $('.page-title-action').each(function() {
                    if ($(this).text().toLowerCase().indexOf('add') !== -1 && $(this).attr('href') && $(this).attr('href').indexOf('lms_course') !== -1) {
                        $(this)
                            .addClass('disabled')
                            .css({'opacity': '0.5', 'cursor': 'not-allowed', 'pointer-events': 'none'})
                            .attr('title', '<?php echo esc_js( __( 'Upgrade to Pro for unlimited courses', 'unstoppable-lms' ) ); ?>');
                    }
                });

                // Disable custom add course buttons
                $('#btn-create-course, .lms-add-course-btn').each(function() {
                    $(this)
                        .addClass('disabled')
                        .prop('disabled', true)
                        .css({'opacity': '0.5', 'cursor': 'not-allowed'})
                        .attr('title', '<?php echo esc_js( __( 'Upgrade to Pro for unlimited courses', 'unstoppable-lms' ) ); ?>')
                        .off('click')
                        .on('click', function(e) {
                            e.preventDefault();
                            alert('<?php echo esc_js( __( 'The free version is limited to 1 course. Upgrade to Pro for just $29.99/year to create unlimited courses!', 'unstoppable-lms' ) ); ?>');
                        });
                });
            });
            </script>
            <?php
        }
    }

    /**
     * Get upgrade prompt HTML.
     *
     * @param string $feature Feature name.
     * @return string
     */
    public static function get_upgrade_prompt( $feature = '' ) {
        $message = __( 'This feature is available in Pro.', 'unstoppable-lms' );

        if ( $feature ) {
            $message = sprintf(
                /* translators: %s: feature name */
                __( '%s is a Pro feature.', 'unstoppable-lms' ),
                $feature
            );
        }

        return sprintf(
            '<div class="unstoppable-lms-upgrade-prompt" style="background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); color: #fff; padding: 30px; border-radius: 8px; text-align: center; margin: 20px 0;">
                <h3 style="margin: 0 0 10px; color: #fff;">🚀 %s</h3>
                <p style="margin: 0 0 20px; opacity: 0.9;">%s</p>
                <a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank" class="button" style="background: #fff; color: #764ba2; border: none; font-weight: bold; padding: 10px 30px; font-size: 14px;">
                    %s
                </a>
                <p style="margin: 15px 0 0; font-size: 12px; opacity: 0.8;">%s</p>
            </div>',
            esc_html( $message ),
            esc_html__( 'Ready to scale? Unlock unlimited courses, advanced gradebook, discussion forums, cohorts, and more.', 'unstoppable-lms' ),
            esc_html__( 'Upgrade to Pro — Just $29.99/year', 'unstoppable-lms' ),
            esc_html__( '✓ 30-day money-back guarantee • Save 85% vs LearnDash', 'unstoppable-lms' )
        );
    }

    /**
     * Render feature locked message.
     *
     * @param string $feature Feature name.
     */
    public static function render_feature_locked( $feature ) {
        echo self::get_upgrade_prompt( $feature ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
