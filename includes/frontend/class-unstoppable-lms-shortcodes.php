<?php
/**
 * Shortcodes.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Shortcodes {

    /**
     * Initialize shortcodes.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_shortcode( 'lms_courses', array( __CLASS__, 'courses' ) );
        add_shortcode( 'lms_my_courses', array( __CLASS__, 'my_courses' ) );
        add_shortcode( 'lms_course_progress', array( __CLASS__, 'course_progress' ) );
        add_shortcode( 'lms_lesson_materials', array( __CLASS__, 'lesson_materials' ) );
    }

    /**
     * Display courses list.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function courses( array $atts = array() ): string {
        $atts = shortcode_atts( array(
            'limit'    => 12,
            'category' => '',
            'columns'  => 3,
        ), $atts );

        $args = array(
            'post_type'      => 'lms_course',
            'posts_per_page' => (int) $atts['limit'],
            'post_status'    => 'publish',
        );

        if ( $atts['category'] ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'lms_course_category',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }

        $courses = get_posts( $args );

        if ( empty( $courses ) ) {
            return '<p>' . esc_html__( 'No courses found.', 'unstoppable-lms' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="lms-courses-grid lms-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
            <?php foreach ( $courses as $course ) : ?>
                <div class="lms-course-card">
                    <?php if ( has_post_thumbnail( $course ) ) : ?>
                        <div class="lms-course-card__image">
                            <a href="<?php echo esc_url( get_permalink( $course ) ); ?>">
                                <?php echo get_the_post_thumbnail( $course, 'medium' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="lms-course-card__content">
                        <h3 class="lms-course-card__title">
                            <a href="<?php echo esc_url( get_permalink( $course ) ); ?>">
                                <?php echo esc_html( $course->post_title ); ?>
                            </a>
                        </h3>
                        <?php if ( $course->post_excerpt ) : ?>
                            <p class="lms-course-card__excerpt">
                                <?php echo esc_html( wp_trim_words( $course->post_excerpt, 20 ) ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display user's enrolled courses.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function my_courses( array $atts = array() ): string {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to view your courses.', 'unstoppable-lms' ) . '</p>';
        }

        $user_id     = get_current_user_id();
        $enrollments = Unstoppable_LMS_Enrollment::get_user_enrollments( $user_id, 'active' );

        if ( empty( $enrollments ) ) {
            return '<p>' . esc_html__( 'You are not enrolled in any courses.', 'unstoppable-lms' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="lms-my-courses">
            <?php foreach ( $enrollments as $enrollment ) :
                $course = get_post( $enrollment['course_id'] );
                if ( ! $course ) continue;
                $progress = Unstoppable_LMS_Progress_Tracker::get_course_progress( $user_id, $course->ID );
            ?>
                <div class="lms-my-course">
                    <div class="lms-my-course__info">
                        <h4 class="lms-my-course__title">
                            <a href="<?php echo esc_url( get_permalink( $course ) ); ?>">
                                <?php echo esc_html( $course->post_title ); ?>
                            </a>
                        </h4>
                        <div class="lms-my-course__progress">
                            <div class="lms-progress-bar">
                                <div class="lms-progress-bar__fill" style="width: <?php echo esc_attr( $progress['percent'] ); ?>%"></div>
                            </div>
                            <span class="lms-progress-bar__text"><?php echo esc_html( $progress['percent'] ); ?>%</span>
                        </div>
                    </div>
                    <div class="lms-my-course__actions">
                        <a href="<?php echo esc_url( get_permalink( $course ) ); ?>" class="lms-btn lms-btn--primary">
                            <?php echo $progress['percent'] > 0 ? esc_html__( 'Continue', 'unstoppable-lms' ) : esc_html__( 'Start', 'unstoppable-lms' ); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display course progress.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function course_progress( array $atts = array() ): string {
        $atts = shortcode_atts( array(
            'course_id' => 0,
        ), $atts );

        if ( ! is_user_logged_in() ) {
            return '';
        }

        $course_id = (int) $atts['course_id'] ?: Unstoppable_LMS_Templates::get_current_course_id();
        if ( ! $course_id ) {
            return '';
        }

        $progress = Unstoppable_LMS_Progress_Tracker::get_course_progress( get_current_user_id(), $course_id );

        ob_start();
        ?>
        <div class="lms-course-progress">
            <div class="lms-progress-bar">
                <div class="lms-progress-bar__fill" style="width: <?php echo esc_attr( $progress['percent'] ); ?>%"></div>
            </div>
            <div class="lms-course-progress__stats">
                <span><?php echo esc_html( $progress['percent'] ); ?>% <?php esc_html_e( 'complete', 'unstoppable-lms' ); ?></span>
                <span><?php echo esc_html( $progress['completed'] ); ?>/<?php echo esc_html( $progress['total'] ); ?> <?php esc_html_e( 'items', 'unstoppable-lms' ); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display lesson downloadable materials.
     *
     * @since 1.2.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function lesson_materials( array $atts = array() ): string {
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts );

        $lesson_id = (int) $atts['id'];

        // If no ID provided, try to get current post
        if ( ! $lesson_id && is_singular( 'lms_lesson' ) ) {
            $lesson_id = get_the_ID();
        }

        if ( ! $lesson_id ) {
            return '';
        }

        return Unstoppable_LMS_Templates::get_lesson_materials( $lesson_id );
    }
}
