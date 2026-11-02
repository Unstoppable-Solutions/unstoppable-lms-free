<?php
/**
 * Settings management for the LMS.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 *
 * Handles plugin settings with smart defaults and contextual overrides.
 * Philosophy: Sensible defaults for 80% of users, settings where they're needed.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Settings {

    /**
     * Default settings.
     *
     * @var array
     */
    private const DEFAULTS = array(
        'course'       => array(
            'progression'       => 'linear',      // linear, free.
            'completion_method' => 'all_items',   // all_items, required_only, manual.
            'materials_access'  => 'enrolled',    // enrolled, everyone.
            'certificate_enabled' => false,
        ),
        'lesson'       => array(
            'completion_method'     => 'manual',  // manual, video_complete, timer.
            'video_autoplay'        => false,
            'video_progress_marker' => true,
        ),
        'quiz'         => array(
            'passing_score'        => 80,
            'attempts_allowed'     => 0,          // 0 = unlimited.
            'show_correct_answers' => 'after_pass', // never, always, after_pass.
            'randomize_questions'  => true,
            'randomize_answers'    => true,
            'time_limit'           => 0,          // minutes, 0 = unlimited.
        ),
        'display'      => array(
            'mode'                  => 'focus',   // focus, sidebar, embedded.
            'show_progress_bar'     => true,
            'show_time_estimates'   => true,
            'sidebar_default_state' => 'collapsed',
            'primary_color'         => '#3B82F6',
            'secondary_color'       => '#10B981',
        ),
        'gamification' => array(
            'enabled'            => false,
            'points_per_lesson'  => 10,
            'points_per_quiz'    => 25,
            'show_leaderboard'   => false,
        ),
        'emails'       => array(
            'enrollment_notification' => true,
            'completion_notification' => true,
            'certificate_notification' => true,
        ),
    );

    /**
     * Initialize settings.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Get a setting value.
     *
     * Supports dot notation: 'course.progression'
     * Also supports item-specific overrides: 'course.progression.123'
     *
     * @since 1.0.0
     * @param string $key     Setting key in dot notation.
     * @param mixed  $default Default value if not found.
     * @return mixed Setting value.
     */
    public static function get( string $key, $default = null ) {
        $parts = explode( '.', $key );

        // Check for item-specific override first.
        if ( count( $parts ) >= 3 && is_numeric( end( $parts ) ) ) {
            $post_id  = array_pop( $parts );
            $meta_key = '_lms_' . implode( '_', $parts );
            $value    = get_post_meta( $post_id, $meta_key, true );

            if ( '' !== $value ) {
                return $value;
            }

            // Remove the ID from key for global lookup.
            $key = implode( '.', $parts );
        }

        // Check global settings.
        $settings = get_option( 'unstoppable_lms_settings', array() );
        $value    = self::array_get( $settings, $key );

        if ( null !== $value ) {
            return $value;
        }

        // Fall back to defaults.
        $default_value = self::array_get( self::DEFAULTS, $key );

        return $default_value ?? $default;
    }

    /**
     * Set a setting value.
     *
     * @since 1.0.0
     * @param string $key   Setting key in dot notation.
     * @param mixed  $value Value to set.
     * @return bool Whether the value was updated.
     */
    public static function set( string $key, $value ): bool {
        $settings = get_option( 'unstoppable_lms_settings', array() );
        self::array_set( $settings, $key, $value );
        return update_option( 'unstoppable_lms_settings', $settings );
    }

    /**
     * Get all settings merged with defaults.
     *
     * @since 1.0.0
     * @return array All settings.
     */
    public static function get_all(): array {
        $settings = get_option( 'unstoppable_lms_settings', array() );
        return self::array_merge_recursive_distinct( self::DEFAULTS, $settings );
    }

    /**
     * Get default settings.
     *
     * @since 1.0.0
     * @return array Default settings.
     */
    public static function get_defaults(): array {
        return self::DEFAULTS;
    }

    /**
     * Register settings for the settings API.
     *
     * @since 1.0.0
     */
    public static function register_settings() {
        register_setting(
            'unstoppable_lms_settings',
            'unstoppable_lms_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
                'default'           => self::DEFAULTS,
            )
        );
    }

    /**
     * Sanitize settings before saving.
     *
     * @since 1.0.0
     * @param array $input Raw input.
     * @return array Sanitized settings.
     */
    public static function sanitize_settings( array $input ): array {
        $sanitized = array();

        // Course settings.
        if ( isset( $input['course'] ) ) {
            $sanitized['course'] = array(
                'progression'       => in_array( $input['course']['progression'] ?? '', array( 'linear', 'free' ), true )
                    ? $input['course']['progression']
                    : 'linear',
                'completion_method' => in_array( $input['course']['completion_method'] ?? '', array( 'all_items', 'required_only', 'manual' ), true )
                    ? $input['course']['completion_method']
                    : 'all_items',
                'materials_access'  => in_array( $input['course']['materials_access'] ?? '', array( 'enrolled', 'everyone' ), true )
                    ? $input['course']['materials_access']
                    : 'enrolled',
                'certificate_enabled' => ! empty( $input['course']['certificate_enabled'] ),
            );
        }

        // Lesson settings.
        if ( isset( $input['lesson'] ) ) {
            $sanitized['lesson'] = array(
                'completion_method'     => in_array( $input['lesson']['completion_method'] ?? '', array( 'manual', 'video_complete', 'timer' ), true )
                    ? $input['lesson']['completion_method']
                    : 'manual',
                'video_autoplay'        => ! empty( $input['lesson']['video_autoplay'] ),
                'video_progress_marker' => ! empty( $input['lesson']['video_progress_marker'] ),
            );
        }

        // Quiz settings.
        if ( isset( $input['quiz'] ) ) {
            $sanitized['quiz'] = array(
                'passing_score'        => max( 0, min( 100, absint( $input['quiz']['passing_score'] ?? 80 ) ) ),
                'attempts_allowed'     => max( 0, absint( $input['quiz']['attempts_allowed'] ?? 0 ) ),
                'show_correct_answers' => in_array( $input['quiz']['show_correct_answers'] ?? '', array( 'never', 'always', 'after_pass' ), true )
                    ? $input['quiz']['show_correct_answers']
                    : 'after_pass',
                'randomize_questions'  => ! empty( $input['quiz']['randomize_questions'] ),
                'randomize_answers'    => ! empty( $input['quiz']['randomize_answers'] ),
                'time_limit'           => max( 0, absint( $input['quiz']['time_limit'] ?? 0 ) ),
            );
        }

        // Display settings.
        if ( isset( $input['display'] ) ) {
            $sanitized['display'] = array(
                'mode'                  => in_array( $input['display']['mode'] ?? '', array( 'focus', 'sidebar', 'embedded' ), true )
                    ? $input['display']['mode']
                    : 'focus',
                'show_progress_bar'     => ! empty( $input['display']['show_progress_bar'] ),
                'show_time_estimates'   => ! empty( $input['display']['show_time_estimates'] ),
                'sidebar_default_state' => in_array( $input['display']['sidebar_default_state'] ?? '', array( 'collapsed', 'expanded' ), true )
                    ? $input['display']['sidebar_default_state']
                    : 'collapsed',
                'primary_color'         => sanitize_hex_color( $input['display']['primary_color'] ?? '#3B82F6' ) ?: '#3B82F6',
                'secondary_color'       => sanitize_hex_color( $input['display']['secondary_color'] ?? '#10B981' ) ?: '#10B981',
            );
        }

        // Gamification settings.
        if ( isset( $input['gamification'] ) ) {
            $sanitized['gamification'] = array(
                'enabled'            => ! empty( $input['gamification']['enabled'] ),
                'points_per_lesson'  => max( 0, absint( $input['gamification']['points_per_lesson'] ?? 10 ) ),
                'points_per_quiz'    => max( 0, absint( $input['gamification']['points_per_quiz'] ?? 25 ) ),
                'show_leaderboard'   => ! empty( $input['gamification']['show_leaderboard'] ),
            );
        }

        // Email settings.
        if ( isset( $input['emails'] ) ) {
            $sanitized['emails'] = array(
                'enrollment_notification'  => ! empty( $input['emails']['enrollment_notification'] ),
                'completion_notification'  => ! empty( $input['emails']['completion_notification'] ),
                'certificate_notification' => ! empty( $input['emails']['certificate_notification'] ),
            );
        }

        return $sanitized;
    }

    /**
     * Get value from array using dot notation.
     *
     * @since 1.0.0
     * @param array  $array Array to search.
     * @param string $key   Key in dot notation.
     * @return mixed|null Value or null if not found.
     */
    private static function array_get( array $array, string $key ) {
        $keys = explode( '.', $key );

        foreach ( $keys as $k ) {
            if ( ! isset( $array[ $k ] ) ) {
                return null;
            }
            $array = $array[ $k ];
        }

        return $array;
    }

    /**
     * Set value in array using dot notation.
     *
     * @since 1.0.0
     * @param array  $array Reference to array.
     * @param string $key   Key in dot notation.
     * @param mixed  $value Value to set.
     */
    private static function array_set( array &$array, string $key, $value ): void {
        $keys = explode( '.', $key );
        $last = array_pop( $keys );

        foreach ( $keys as $k ) {
            if ( ! isset( $array[ $k ] ) || ! is_array( $array[ $k ] ) ) {
                $array[ $k ] = array();
            }
            $array = &$array[ $k ];
        }

        $array[ $last ] = $value;
    }

    /**
     * Recursively merge arrays, replacing values instead of merging them.
     *
     * @since 1.0.0
     * @param array $array1 Base array.
     * @param array $array2 Array to merge.
     * @return array Merged array.
     */
    private static function array_merge_recursive_distinct( array $array1, array $array2 ): array {
        $merged = $array1;

        foreach ( $array2 as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = self::array_merge_recursive_distinct( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }

        return $merged;
    }
}
