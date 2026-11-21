<?php
/**
 * Dashboard Widgets for Unstoppable LMS
 *
 * Adds widgets to the WordPress admin dashboard and student-facing areas.
 *
 * @package Unstoppable_LMS
 * @since 1.7.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Widgets class.
 *
 * @since 1.7.0
 */
class Unstoppable_LMS_Widgets {

    /**
     * Initialize widgets.
     *
     * @since 1.7.0
     */
    public static function init() {
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widgets' ) );
        add_shortcode( 'lms_my_courses', array( __CLASS__, 'my_courses_shortcode' ) );
        add_shortcode( 'lms_continue_learning', array( __CLASS__, 'continue_learning_shortcode' ) );
    }

    /**
     * Add dashboard widgets.
     *
     * @since 1.7.0
     */
    public static function add_dashboard_widgets() {
        // Admin stats widget.
        if ( current_user_can( 'edit_lms_courses' ) ) {
            wp_add_dashboard_widget(
                'unstoppable_lms_stats',
                __( '📚 Unstoppable LMS Overview', 'unstoppable-lms' ),
                array( __CLASS__, 'render_admin_stats_widget' )
            );
        }

        // Student progress widget.
        if ( is_user_logged_in() && ! current_user_can( 'edit_lms_courses' ) ) {
            wp_add_dashboard_widget(
                'unstoppable_lms_my_progress',
                __( '📖 My Learning Progress', 'unstoppable-lms' ),
                array( __CLASS__, 'render_student_progress_widget' )
            );
        }
    }

    /**
     * Render admin stats widget.
     *
     * @since 1.7.0
     */
    public static function render_admin_stats_widget() {
        global $wpdb;

        // Get quick stats.
        $total_courses = wp_count_posts( 'lms_course' )->publish;
        $total_lessons = wp_count_posts( 'lms_lesson' )->publish;

        $enrollments_table = $wpdb->prefix . 'lms_enrollments';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_enrollments = $wpdb->get_var( "SELECT COUNT(*) FROM {$enrollments_table} WHERE status = 'active'" );
        $total_enrollments = $total_enrollments ? intval( $total_enrollments ) : 0;

        $progress_table = $wpdb->prefix . 'lms_progress';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $completions = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id, course_id) FROM {$progress_table} WHERE status = 'completed'"
        );
        $completions = $completions ? intval( $completions ) : 0;

        // Calculate completion rate.
        $completion_rate = $total_enrollments > 0 ? round( ( $completions / $total_enrollments ) * 100, 1 ) : 0;
        ?>
        <div class="unstoppable-lms-stats-widget">
            <style>
                .unstoppable-lms-stats-widget .stat-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                    margin-bottom: 15px;
                }
                .unstoppable-lms-stats-widget .stat-box {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                }
                .unstoppable-lms-stats-widget .stat-number {
                    font-size: 28px;
                    font-weight: bold;
                    color: #1d2327;
                    display: block;
                }
                .unstoppable-lms-stats-widget .stat-label {
                    color: #646970;
                    font-size: 12px;
                    text-transform: uppercase;
                }
                .unstoppable-lms-stats-widget .quick-links {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .unstoppable-lms-stats-widget .quick-links a {
                    background: #f0b849;
                    color: #000;
                    padding: 8px 15px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-weight: 500;
                    font-size: 13px;
                }
                .unstoppable-lms-stats-widget .quick-links a:hover {
                    background: #d9a63d;
                }
            </style>

            <div class="stat-grid">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html( $total_courses ); ?></span>
                    <span class="stat-label"><?php esc_html_e( 'Courses', 'unstoppable-lms' ); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html( $total_lessons ); ?></span>
                    <span class="stat-label"><?php esc_html_e( 'Lessons', 'unstoppable-lms' ); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html( $total_enrollments ); ?></span>
                    <span class="stat-label"><?php esc_html_e( 'Enrolled Students', 'unstoppable-lms' ); ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html( $completion_rate ); ?>%</span>
                    <span class="stat-label"><?php esc_html_e( 'Completion Rate', 'unstoppable-lms' ); ?></span>
                </div>
            </div>

            <div class="quick-links">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms' ) ); ?>">
                    <?php esc_html_e( 'Dashboard', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-courses' ) ); ?>">
                    <?php esc_html_e( 'Manage Courses', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-students' ) ); ?>">
                    <?php esc_html_e( 'View Students', 'unstoppable-lms' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render student progress widget.
     *
     * @since 1.7.0
     */
    public static function render_student_progress_widget() {
        $user_id = get_current_user_id();
        $courses = self::get_user_enrolled_courses( $user_id );

        if ( empty( $courses ) ) {
            echo '<p>' . esc_html__( 'You are not enrolled in any courses yet.', 'unstoppable-lms' ) . '</p>';
            return;
        }
        ?>
        <div class="unstoppable-lms-progress-widget">
            <style>
                .unstoppable-lms-progress-widget .course-item {
                    padding: 12px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                .unstoppable-lms-progress-widget .course-item:last-child {
                    border-bottom: none;
                }
                .unstoppable-lms-progress-widget .course-title {
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                .unstoppable-lms-progress-widget .progress-bar {
                    background: #e0e0e0;
                    height: 8px;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-bottom: 8px;
                }
                .unstoppable-lms-progress-widget .progress-fill {
                    background: #f0b849;
                    height: 100%;
                    transition: width 0.3s ease;
                }
                .unstoppable-lms-progress-widget .progress-text {
                    font-size: 12px;
                    color: #646970;
                }
                .unstoppable-lms-progress-widget .continue-btn {
                    display: inline-block;
                    background: #f0b849;
                    color: #000;
                    padding: 6px 12px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-size: 12px;
                    font-weight: 500;
                    margin-top: 5px;
                }
                .unstoppable-lms-progress-widget .continue-btn:hover {
                    background: #d9a63d;
                }
            </style>

            <?php foreach ( $courses as $course ) : ?>
                <div class="course-item">
                    <div class="course-title"><?php echo esc_html( $course['title'] ); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr( $course['progress'] ); ?>%;"></div>
                    </div>
                    <div class="progress-text">
                        <?php
                        printf(
                            /* translators: 1: progress percentage, 2: completed lessons, 3: total lessons */
                            esc_html__( '%1$s%% complete (%2$d of %3$d lessons)', 'unstoppable-lms' ),
                            esc_html( $course['progress'] ),
                            esc_html( $course['completed_lessons'] ),
                            esc_html( $course['total_lessons'] )
                        );
                        ?>
                    </div>
                    <?php if ( $course['next_lesson_url'] ) : ?>
                        <a href="<?php echo esc_url( $course['next_lesson_url'] ); ?>" class="continue-btn">
                            <?php esc_html_e( '▶ Continue Learning', 'unstoppable-lms' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get user's enrolled courses with progress.
     *
     * @since 1.7.0
     * @param int $user_id User ID.
     * @return array Courses with progress data.
     */
    public static function get_user_enrolled_courses( $user_id ) {
        global $wpdb;

        $enrollments_table = $wpdb->prefix . 'lms_enrollments';
        $progress_table    = $wpdb->prefix . 'lms_progress';

        // Get active enrollments.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $enrollments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT course_id FROM {$enrollments_table} WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );

        if ( empty( $enrollments ) ) {
            return array();
        }

        $courses = array();

        foreach ( $enrollments as $enrollment ) {
            $course = get_post( $enrollment->course_id );
            if ( ! $course || 'publish' !== $course->post_status ) {
                continue;
            }

            // Get lessons for this course.
            $lessons = get_posts( array(
                'post_type'      => 'lms_lesson',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_course_id',
                        'value' => $course->ID,
                    ),
                ),
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ) );

            $total_lessons = count( $lessons );
            $completed_lessons = 0;
            $next_lesson_id = null;

            foreach ( $lessons as $lesson ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $completed = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT status FROM {$progress_table} WHERE user_id = %d AND lesson_id = %d AND course_id = %d",
                        $user_id,
                        $lesson->ID,
                        $course->ID
                    )
                );

                if ( 'completed' === $completed ) {
                    $completed_lessons++;
                } elseif ( null === $next_lesson_id ) {
                    $next_lesson_id = $lesson->ID;
                }
            }

            // If all complete, no next lesson.
            if ( $completed_lessons === $total_lessons ) {
                $next_lesson_id = null;
            }

            $progress = $total_lessons > 0 ? round( ( $completed_lessons / $total_lessons ) * 100 ) : 0;

            $courses[] = array(
                'id'                => $course->ID,
                'title'             => $course->post_title,
                'progress'          => $progress,
                'total_lessons'     => $total_lessons,
                'completed_lessons' => $completed_lessons,
                'next_lesson_url'   => $next_lesson_id ? get_permalink( $next_lesson_id ) : null,
            );
        }

        return $courses;
    }

    /**
     * My Courses shortcode.
     *
     * @since 1.7.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function my_courses_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to view your courses.', 'unstoppable-lms' ) . '</p>';
        }

        ob_start();
        self::render_student_progress_widget();
        return ob_get_clean();
    }

    /**
     * Continue Learning shortcode.
     *
     * @since 1.7.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function continue_learning_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $user_id = get_current_user_id();
        $courses = self::get_user_enrolled_courses( $user_id );

        // Find the most recent incomplete course.
        foreach ( $courses as $course ) {
            if ( $course['next_lesson_url'] && $course['progress'] < 100 ) {
                return sprintf(
                    '<a href="%s" class="lms-continue-learning-btn" style="display:inline-block;background:#f0b849;color:#000;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;">%s %s</a>',
                    esc_url( $course['next_lesson_url'] ),
                    esc_html__( '▶ Continue:', 'unstoppable-lms' ),
                    esc_html( $course['title'] )
                );
            }
        }

        return '';
    }
}
