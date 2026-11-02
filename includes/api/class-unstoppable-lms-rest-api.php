<?php
/**
 * REST API endpoints.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * REST API class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_REST_API {

    /**
     * Namespace for the API.
     *
     * @var string
     */
    const NAMESPACE = 'unstoppable-lms/v1';

    /**
     * Register REST routes.
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Progress endpoints.
        register_rest_route(
            self::NAMESPACE,
            '/progress/complete',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'complete_item' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => array(
                    'item_id'   => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                    'item_type' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array( 'lesson', 'topic', 'quiz' ),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/progress/(?P<course_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_progress' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => array(
                    'course_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Enrollment endpoints.
        register_rest_route(
            self::NAMESPACE,
            '/enroll',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'enroll_user' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
                'args'                => array(
                    'user_id'   => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                    'course_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Self-enrollment endpoint for logged-in users.
        register_rest_route(
            self::NAMESPACE,
            '/enroll/self',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'self_enroll' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => array(
                    'course_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Course structure endpoint.
        register_rest_route(
            self::NAMESPACE,
            '/courses/(?P<id>\d+)/structure',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_course_structure' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Quiz submission endpoint.
        register_rest_route(
            self::NAMESPACE,
            '/quiz/submit',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'submit_quiz' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => array(
                    'quiz_id'    => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                    'attempt_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => array( $this, 'validate_positive_int' ),
                        'sanitize_callback' => 'absint',
                    ),
                    'answers'    => array(
                        'required' => true,
                        'type'     => 'object',
                    ),
                ),
            )
        );
    }

    /**
     * Validate positive integer.
     *
     * @since 1.3.0
     * @param mixed $value Value to validate.
     * @return bool Whether valid.
     */
    public function validate_positive_int( $value ): bool {
        return is_numeric( $value ) && intval( $value ) > 0;
    }

    /**
     * Check if user is logged in.
     *
     * @since 1.0.0
     * @return bool Whether user has permission.
     */
    public function check_user_permission(): bool {
        return is_user_logged_in();
    }

    /**
     * Check if user is admin.
     *
     * @since 1.0.0
     * @return bool Whether user has permission.
     */
    public function check_admin_permission(): bool {
        return current_user_can( 'manage_lms_students' );
    }

    /**
     * Complete an item.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function complete_item( WP_REST_Request $request ): WP_REST_Response {
        $user_id   = get_current_user_id();
        $item_id   = $request->get_param( 'item_id' );
        $item_type = $request->get_param( 'item_type' );
        $meta      = $request->get_param( 'meta' );
        
        if ( ! is_array( $meta ) ) {
            $meta = array();
        }

        // Validate item exists
        $item = get_post( $item_id );
        if ( ! $item ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'Item not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        // Validate item type matches
        $expected_post_type = 'lms_' . $item_type;
        if ( $item->post_type !== $expected_post_type ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'Invalid item type.', 'unstoppable-lms' ) ), 
                400 
            );
        }

        $result = Unstoppable_LMS_Progress_Tracker::complete_item( $user_id, $item_id, $item_type, $meta );

        if ( ! $result['success'] ) {
            return new WP_REST_Response( $result, 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Get progress for a course.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_progress( WP_REST_Request $request ): WP_REST_Response {
        $user_id   = get_current_user_id();
        $course_id = $request->get_param( 'course_id' );

        // Validate course exists
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return new WP_REST_Response( 
                array( 'error' => __( 'Course not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        $progress = Unstoppable_LMS_Progress_Tracker::get_course_progress( $user_id, $course_id );

        return new WP_REST_Response( $progress, 200 );
    }

    /**
     * Enroll a user (admin action).
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function enroll_user( WP_REST_Request $request ): WP_REST_Response {
        $user_id   = $request->get_param( 'user_id' );
        $course_id = $request->get_param( 'course_id' );

        // Validate user exists
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'User not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        // Validate course exists
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'Course not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        $result = Unstoppable_LMS_Enrollment::enroll( $user_id, $course_id, null, get_current_user_id() );

        if ( ! $result['success'] ) {
            return new WP_REST_Response( $result, 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Self-enroll in a course.
     *
     * @since 1.3.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function self_enroll( WP_REST_Request $request ): WP_REST_Response {
        $user_id   = get_current_user_id();
        $course_id = $request->get_param( 'course_id' );

        // Validate course exists
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'Course not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        // Check if course allows self-enrollment
        $access_type = get_post_meta( $course_id, '_lms_access_type', true );
        if ( 'closed' === $access_type ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'This course does not allow self-enrollment.', 'unstoppable-lms' ) ), 
                403 
            );
        }

        if ( 'paid' === $access_type ) {
            return new WP_REST_Response( 
                array( 'success' => false, 'message' => __( 'This course requires purchase.', 'unstoppable-lms' ) ), 
                403 
            );
        }

        // Check max students
        $max_students = absint( get_post_meta( $course_id, '_lms_max_students', true ) );
        if ( $max_students > 0 ) {
            $current_count = Unstoppable_LMS_Enrollment::count_course_students( $course_id );
            if ( $current_count >= $max_students ) {
                return new WP_REST_Response( 
                    array( 'success' => false, 'message' => __( 'This course is full.', 'unstoppable-lms' ) ), 
                    403 
                );
            }
        }

        $result = Unstoppable_LMS_Enrollment::enroll( $user_id, $course_id );

        if ( ! $result['success'] ) {
            return new WP_REST_Response( $result, 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Get course structure.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_course_structure( WP_REST_Request $request ): WP_REST_Response {
        $course_id = $request->get_param( 'id' );
        
        // Validate course exists
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return new WP_REST_Response( 
                array( 'error' => __( 'Course not found.', 'unstoppable-lms' ) ), 
                404 
            );
        }

        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );

        if ( ! $structure ) {
            return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
        }

        return new WP_REST_Response( $structure, 200 );
    }

    /**
     * Submit a quiz.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function submit_quiz( WP_REST_Request $request ): WP_REST_Response {
        // TODO: Implement quiz scoring logic.
        return new WP_REST_Response( 
            array( 'message' => __( 'Quiz submission coming soon', 'unstoppable-lms' ) ), 
            200 
        );
    }
}
