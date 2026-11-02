<?php
/**
 * Course functionality.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Course class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Course {

    /**
     * Get a course by ID.
     *
     * @since 1.0.0
     * @param int $course_id Course ID.
     * @return WP_Post|null Course post or null.
     */
    public static function get( int $course_id ): ?WP_Post {
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return null;
        }
        return $course;
    }

    /**
     * Get course structure.
     *
     * @since 1.0.0
     * @param int $course_id Course ID.
     * @return array|null Course structure or null.
     */
    public static function get_structure( int $course_id ): ?array {
        return Unstoppable_LMS_Cache::get_course_structure( $course_id );
    }
}
