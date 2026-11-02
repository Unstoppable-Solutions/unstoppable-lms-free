<?php
/**
 * Enrollment management.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Enrollment class.
 *
 * Handles user enrollment in courses.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Enrollment {

    /**
     * Enroll a user in a course.
     *
     * @since 1.0.0
     * @param int      $user_id    User ID.
     * @param int      $course_id  Course ID.
     * @param int|null $expires_at Optional expiration timestamp.
     * @param int|null $enrolled_by Optional user ID who enrolled them.
     * @return array Result with success status.
     */
    public static function enroll( int $user_id, int $course_id, ?int $expires_at = null, ?int $enrolled_by = null ): array {
        global $wpdb;

        // Check if already enrolled.
        $existing = self::get_enrollment( $user_id, $course_id );
        if ( $existing && 'active' === $existing['status'] ) {
            return array(
                'success' => true,
                'message' => 'already_enrolled',
            );
        }

        $table = $wpdb->prefix . 'lms_enrollments';

        $data = array(
            'user_id'     => $user_id,
            'course_id'   => $course_id,
            'enrolled_at' => current_time( 'mysql' ),
            'expires_at'  => $expires_at ? gmdate( 'Y-m-d H:i:s', $expires_at ) : null,
            'status'      => 'active',
            'enrolled_by' => $enrolled_by,
        );

        if ( $existing ) {
            // Re-enroll.
            $result = $wpdb->update(
                $table,
                $data,
                array( 'id' => $existing['id'] ),
                array( '%d', '%d', '%s', '%s', '%s', '%d' ),
                array( '%d' )
            );
        } else {
            $result = $wpdb->insert(
                $table,
                $data,
                array( '%d', '%d', '%s', '%s', '%s', '%d' )
            );
        }

        if ( false === $result ) {
            return array(
                'success' => false,
                'error'   => 'database_error',
            );
        }

        Unstoppable_LMS_Cache::invalidate_enrollment( $user_id, $course_id );

        do_action( 'unstoppable_lms_user_enrolled', $user_id, $course_id );

        return array(
            'success' => true,
        );
    }

    /**
     * Unenroll a user from a course.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return array Result with success status.
     */
    public static function unenroll( int $user_id, int $course_id ): array {
        global $wpdb;

        $table = $wpdb->prefix . 'lms_enrollments';

        $result = $wpdb->update(
            $table,
            array( 'status' => 'suspended' ),
            array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
            ),
            array( '%s' ),
            array( '%d', '%d' )
        );

        Unstoppable_LMS_Cache::invalidate_enrollment( $user_id, $course_id );

        do_action( 'unstoppable_lms_user_unenrolled', $user_id, $course_id );

        return array(
            'success' => false !== $result,
        );
    }

    /**
     * Get enrollment data.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return array|null Enrollment data or null.
     */
    public static function get_enrollment( int $user_id, int $course_id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_enrollments';

        $enrollment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d",
                $user_id,
                $course_id
            ),
            ARRAY_A
        );

        return $enrollment;
    }

    /**
     * Check if user is enrolled.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return bool Whether user is enrolled.
     */
    public static function is_enrolled( int $user_id, int $course_id ): bool {
        $enrollment = Unstoppable_LMS_Cache::get_enrollment( $user_id, $course_id );
        return null !== $enrollment;
    }

    /**
     * Get all enrollments for a user.
     *
     * @since 1.0.0
     * @param int    $user_id User ID.
     * @param string $status  Optional status filter.
     * @return array List of enrollments.
     */
    public static function get_user_enrollments( int $user_id, string $status = '' ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_enrollments';

        $sql = "SELECT * FROM {$table} WHERE user_id = %d";
        $params = array( $user_id );

        if ( $status ) {
            $sql .= ' AND status = %s';
            $params[] = $status;
        }

        $sql .= ' ORDER BY enrolled_at DESC';

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
    }

    /**
     * Get all students enrolled in a course.
     *
     * @since 1.0.0
     * @param int    $course_id Course ID.
     * @param string $status    Optional status filter.
     * @param int    $limit     Optional limit.
     * @param int    $offset    Optional offset.
     * @return array List of enrollments with user data.
     */
    public static function get_course_students( int $course_id, string $status = 'active', int $limit = 20, int $offset = 0 ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_enrollments';

        $sql = "SELECT e.*, u.user_login, u.user_email, u.display_name
                FROM {$table} e
                JOIN {$wpdb->users} u ON e.user_id = u.ID
                WHERE e.course_id = %d";
        $params = array( $course_id );

        if ( $status ) {
            $sql .= ' AND e.status = %s';
            $params[] = $status;
        }

        $sql .= ' ORDER BY e.enrolled_at DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
    }

    /**
     * Count students in a course.
     *
     * @since 1.0.0
     * @param int    $course_id Course ID.
     * @param string $status    Optional status filter.
     * @return int Student count.
     */
    public static function count_course_students( int $course_id, string $status = 'active' ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_enrollments';

        $sql = "SELECT COUNT(*) FROM {$table} WHERE course_id = %d";
        $params = array( $course_id );

        if ( $status ) {
            $sql .= ' AND status = %s';
            $params[] = $status;
        }

        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    }
}
