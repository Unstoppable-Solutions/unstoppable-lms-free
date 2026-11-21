<?php
/**
 * Email Notifications for Unstoppable LMS
 *
 * Handles sending email notifications for course events.
 *
 * @package Unstoppable_LMS
 * @since 1.7.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Emails class.
 *
 * @since 1.7.0
 */
class Unstoppable_LMS_Emails {

    /**
     * Initialize email hooks.
     *
     * @since 1.7.0
     */
    public static function init() {
        add_action( 'unstoppable_lms_course_completed', array( __CLASS__, 'send_completion_email' ), 10, 2 );
        add_action( 'unstoppable_lms_student_enrolled', array( __CLASS__, 'send_enrollment_email' ), 10, 2 );
    }

    /**
     * Send course completion email to student.
     *
     * @since 1.7.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    public static function send_completion_email( $user_id, $course_id ) {
        // Check if completion emails are enabled.
        $emails_enabled = get_option( 'unstoppable_lms_completion_emails', true );
        if ( ! $emails_enabled ) {
            return;
        }

        $user   = get_user_by( 'id', $user_id );
        $course = get_post( $course_id );

        if ( ! $user || ! $course ) {
            return;
        }

        $to      = $user->user_email;
        $subject = sprintf(
            /* translators: %s: course title */
            __( '🎉 Congratulations! You completed "%s"', 'unstoppable-lms' ),
            $course->post_title
        );

        $site_name = get_bloginfo( 'name' );
        $course_url = get_permalink( $course_id );

        $message = sprintf(
            /* translators: 1: user display name, 2: course title, 3: site name */
            __( 'Hi %1$s,

Congratulations on completing "%2$s"! 🎓

You\'ve successfully finished all the lessons in this course. This is a great achievement, and you should be proud of yourself.

What\'s next?
- Review the course material anytime
- Share your accomplishment with others
- Check out other courses available to you

Keep up the great work!

Best regards,
%3$s', 'unstoppable-lms' ),
            $user->display_name,
            $course->post_title,
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
        );

        wp_mail( $to, $subject, $message, $headers );

        /**
         * Fires after a course completion email is sent.
         *
         * @since 1.7.0
         * @param int    $user_id   User ID.
         * @param int    $course_id Course ID.
         * @param string $to        Email address.
         */
        do_action( 'unstoppable_lms_completion_email_sent', $user_id, $course_id, $to );
    }

    /**
     * Send enrollment confirmation email to student.
     *
     * @since 1.7.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    public static function send_enrollment_email( $user_id, $course_id ) {
        // Check if enrollment emails are enabled.
        $emails_enabled = get_option( 'unstoppable_lms_enrollment_emails', true );
        if ( ! $emails_enabled ) {
            return;
        }

        $user   = get_user_by( 'id', $user_id );
        $course = get_post( $course_id );

        if ( ! $user || ! $course ) {
            return;
        }

        $to      = $user->user_email;
        $subject = sprintf(
            /* translators: %s: course title */
            __( '📚 You\'re enrolled in "%s"', 'unstoppable-lms' ),
            $course->post_title
        );

        $site_name   = get_bloginfo( 'name' );
        $course_url  = get_permalink( $course_id );
        $lessons     = get_posts( array(
            'post_type'      => 'lms_lesson',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_course_id',
                    'value' => $course_id,
                ),
            ),
        ) );
        $lesson_count = count( $lessons );

        $message = sprintf(
            /* translators: 1: user display name, 2: course title, 3: lesson count, 4: course URL, 5: site name */
            __( 'Hi %1$s,

Welcome to "%2$s"! 🎉

You\'ve been enrolled in this course, which contains %3$d lessons. You can start learning right away.

Start your course here:
%4$s

Happy learning!

Best regards,
%5$s', 'unstoppable-lms' ),
            $user->display_name,
            $course->post_title,
            $lesson_count,
            $course_url,
            $site_name
        );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
        );

        wp_mail( $to, $subject, $message, $headers );
    }

    /**
     * Get email template.
     *
     * @since 1.7.0
     * @param string $template Template name.
     * @return string Template path.
     */
    public static function get_template( $template ) {
        $template_path = UNSTOPPABLE_LMS_PLUGIN_DIR . 'templates/emails/' . $template . '.php';

        if ( file_exists( $template_path ) ) {
            return $template_path;
        }

        return '';
    }
}
