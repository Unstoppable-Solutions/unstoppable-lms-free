<?php
/**
 * Custom Post Types registration.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Post Types class.
 *
 * Registers all custom post types and taxonomies for the LMS.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Post_Types {

    /**
     * Register all post types and taxonomies.
     *
     * @since 1.0.0
     */
    public static function register() {
        self::register_post_types();
        self::register_taxonomies();
    }

    /**
     * Register custom post types.
     *
     * @since 1.0.0
     */
    public static function register_post_types() {
        // Course post type.
        register_post_type(
            'lms_course',
            array(
                'labels'              => array(
                    'name'                  => _x( 'Courses', 'Post type general name', 'unstoppable-lms' ),
                    'singular_name'         => _x( 'Course', 'Post type singular name', 'unstoppable-lms' ),
                    'menu_name'             => _x( 'Courses', 'Admin Menu text', 'unstoppable-lms' ),
                    'add_new'               => __( 'Add New', 'unstoppable-lms' ),
                    'add_new_item'          => __( 'Add New Course', 'unstoppable-lms' ),
                    'edit_item'             => __( 'Edit Course', 'unstoppable-lms' ),
                    'new_item'              => __( 'New Course', 'unstoppable-lms' ),
                    'view_item'             => __( 'View Course', 'unstoppable-lms' ),
                    'search_items'          => __( 'Search Courses', 'unstoppable-lms' ),
                    'not_found'             => __( 'No courses found', 'unstoppable-lms' ),
                    'not_found_in_trash'    => __( 'No courses found in Trash', 'unstoppable-lms' ),
                    'all_items'             => __( 'All Courses', 'unstoppable-lms' ),
                    'archives'              => __( 'Course Archives', 'unstoppable-lms' ),
                    'attributes'            => __( 'Course Attributes', 'unstoppable-lms' ),
                    'insert_into_item'      => __( 'Insert into course', 'unstoppable-lms' ),
                    'uploaded_to_this_item' => __( 'Uploaded to this course', 'unstoppable-lms' ),
                    'filter_items_list'     => __( 'Filter courses list', 'unstoppable-lms' ),
                    'items_list_navigation' => __( 'Courses list navigation', 'unstoppable-lms' ),
                    'items_list'            => __( 'Courses list', 'unstoppable-lms' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false, // We'll add to our custom menu.
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'courses', 'with_front' => false ),
                'capability_type'     => array( 'lms_course', 'lms_courses' ),
                'map_meta_cap'        => true,
                'has_archive'         => true,
                'hierarchical'        => false,
                'menu_icon'           => 'dashicons-welcome-learn-more',
                'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ),
                'show_in_rest'        => true,
                'rest_base'           => 'lms-courses',
            )
        );

        // Lesson post type.
        register_post_type(
            'lms_lesson',
            array(
                'labels'              => array(
                    'name'                  => _x( 'Lessons', 'Post type general name', 'unstoppable-lms' ),
                    'singular_name'         => _x( 'Lesson', 'Post type singular name', 'unstoppable-lms' ),
                    'menu_name'             => _x( 'Lessons', 'Admin Menu text', 'unstoppable-lms' ),
                    'add_new'               => __( 'Add New', 'unstoppable-lms' ),
                    'add_new_item'          => __( 'Add New Lesson', 'unstoppable-lms' ),
                    'edit_item'             => __( 'Edit Lesson', 'unstoppable-lms' ),
                    'new_item'              => __( 'New Lesson', 'unstoppable-lms' ),
                    'view_item'             => __( 'View Lesson', 'unstoppable-lms' ),
                    'search_items'          => __( 'Search Lessons', 'unstoppable-lms' ),
                    'not_found'             => __( 'No lessons found', 'unstoppable-lms' ),
                    'not_found_in_trash'    => __( 'No lessons found in Trash', 'unstoppable-lms' ),
                    'all_items'             => __( 'All Lessons', 'unstoppable-lms' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'lessons', 'with_front' => false ),
                'capability_type'     => array( 'lms_lesson', 'lms_lessons' ),
                'map_meta_cap'        => true,
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'comments' ),
                'show_in_rest'        => true,
                'rest_base'           => 'lms-lessons',
            )
        );

        // Topic post type (sub-lessons).
        register_post_type(
            'lms_topic',
            array(
                'labels'              => array(
                    'name'                  => _x( 'Topics', 'Post type general name', 'unstoppable-lms' ),
                    'singular_name'         => _x( 'Topic', 'Post type singular name', 'unstoppable-lms' ),
                    'menu_name'             => _x( 'Topics', 'Admin Menu text', 'unstoppable-lms' ),
                    'add_new'               => __( 'Add New', 'unstoppable-lms' ),
                    'add_new_item'          => __( 'Add New Topic', 'unstoppable-lms' ),
                    'edit_item'             => __( 'Edit Topic', 'unstoppable-lms' ),
                    'new_item'              => __( 'New Topic', 'unstoppable-lms' ),
                    'view_item'             => __( 'View Topic', 'unstoppable-lms' ),
                    'search_items'          => __( 'Search Topics', 'unstoppable-lms' ),
                    'not_found'             => __( 'No topics found', 'unstoppable-lms' ),
                    'not_found_in_trash'    => __( 'No topics found in Trash', 'unstoppable-lms' ),
                    'all_items'             => __( 'All Topics', 'unstoppable-lms' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'topics', 'with_front' => false ),
                'capability_type'     => array( 'lms_topic', 'lms_topics' ),
                'map_meta_cap'        => true,
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'revisions' ),
                'show_in_rest'        => true,
                'rest_base'           => 'lms-topics',
            )
        );

        // Quiz post type.
        register_post_type(
            'lms_quiz',
            array(
                'labels'              => array(
                    'name'                  => _x( 'Quizzes', 'Post type general name', 'unstoppable-lms' ),
                    'singular_name'         => _x( 'Quiz', 'Post type singular name', 'unstoppable-lms' ),
                    'menu_name'             => _x( 'Quizzes', 'Admin Menu text', 'unstoppable-lms' ),
                    'add_new'               => __( 'Add New', 'unstoppable-lms' ),
                    'add_new_item'          => __( 'Add New Quiz', 'unstoppable-lms' ),
                    'edit_item'             => __( 'Edit Quiz', 'unstoppable-lms' ),
                    'new_item'              => __( 'New Quiz', 'unstoppable-lms' ),
                    'view_item'             => __( 'View Quiz', 'unstoppable-lms' ),
                    'search_items'          => __( 'Search Quizzes', 'unstoppable-lms' ),
                    'not_found'             => __( 'No quizzes found', 'unstoppable-lms' ),
                    'not_found_in_trash'    => __( 'No quizzes found in Trash', 'unstoppable-lms' ),
                    'all_items'             => __( 'All Quizzes', 'unstoppable-lms' ),
                ),
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'quizzes', 'with_front' => false ),
                'capability_type'     => array( 'lms_quiz', 'lms_quizzes' ),
                'map_meta_cap'        => true,
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'author', 'revisions' ),
                'show_in_rest'        => true,
                'rest_base'           => 'lms-quizzes',
            )
        );

        // Certificate post type.
        register_post_type(
            'lms_certificate',
            array(
                'labels'              => array(
                    'name'                  => _x( 'Certificates', 'Post type general name', 'unstoppable-lms' ),
                    'singular_name'         => _x( 'Certificate', 'Post type singular name', 'unstoppable-lms' ),
                    'menu_name'             => _x( 'Certificates', 'Admin Menu text', 'unstoppable-lms' ),
                    'add_new'               => __( 'Add New', 'unstoppable-lms' ),
                    'add_new_item'          => __( 'Add New Certificate', 'unstoppable-lms' ),
                    'edit_item'             => __( 'Edit Certificate', 'unstoppable-lms' ),
                    'new_item'              => __( 'New Certificate', 'unstoppable-lms' ),
                    'view_item'             => __( 'View Certificate', 'unstoppable-lms' ),
                    'search_items'          => __( 'Search Certificates', 'unstoppable-lms' ),
                    'not_found'             => __( 'No certificates found', 'unstoppable-lms' ),
                    'not_found_in_trash'    => __( 'No certificates found in Trash', 'unstoppable-lms' ),
                    'all_items'             => __( 'All Certificates', 'unstoppable-lms' ),
                ),
                'public'              => false,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'certificates', 'with_front' => false ),
                'capability_type'     => 'post',
                'has_archive'         => false,
                'hierarchical'        => false,
                'supports'            => array( 'title', 'editor', 'thumbnail' ),
                'show_in_rest'        => true,
                'rest_base'           => 'lms-certificates',
            )
        );
    }

    /**
     * Register taxonomies.
     *
     * @since 1.0.0
     */
    public static function register_taxonomies() {
        // Course category.
        register_taxonomy(
            'lms_course_category',
            'lms_course',
            array(
                'labels'            => array(
                    'name'              => _x( 'Course Categories', 'taxonomy general name', 'unstoppable-lms' ),
                    'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'unstoppable-lms' ),
                    'search_items'      => __( 'Search Course Categories', 'unstoppable-lms' ),
                    'all_items'         => __( 'All Course Categories', 'unstoppable-lms' ),
                    'parent_item'       => __( 'Parent Course Category', 'unstoppable-lms' ),
                    'parent_item_colon' => __( 'Parent Course Category:', 'unstoppable-lms' ),
                    'edit_item'         => __( 'Edit Course Category', 'unstoppable-lms' ),
                    'update_item'       => __( 'Update Course Category', 'unstoppable-lms' ),
                    'add_new_item'      => __( 'Add New Course Category', 'unstoppable-lms' ),
                    'new_item_name'     => __( 'New Course Category Name', 'unstoppable-lms' ),
                    'menu_name'         => __( 'Categories', 'unstoppable-lms' ),
                ),
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'course-category' ),
                'show_in_rest'      => true,
                'rest_base'         => 'lms-course-categories',
            )
        );

        // Course tags.
        register_taxonomy(
            'lms_course_tag',
            'lms_course',
            array(
                'labels'            => array(
                    'name'              => _x( 'Course Tags', 'taxonomy general name', 'unstoppable-lms' ),
                    'singular_name'     => _x( 'Course Tag', 'taxonomy singular name', 'unstoppable-lms' ),
                    'search_items'      => __( 'Search Course Tags', 'unstoppable-lms' ),
                    'all_items'         => __( 'All Course Tags', 'unstoppable-lms' ),
                    'edit_item'         => __( 'Edit Course Tag', 'unstoppable-lms' ),
                    'update_item'       => __( 'Update Course Tag', 'unstoppable-lms' ),
                    'add_new_item'      => __( 'Add New Course Tag', 'unstoppable-lms' ),
                    'new_item_name'     => __( 'New Course Tag Name', 'unstoppable-lms' ),
                    'menu_name'         => __( 'Tags', 'unstoppable-lms' ),
                ),
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'course-tag' ),
                'show_in_rest'      => true,
                'rest_base'         => 'lms-course-tags',
            )
        );

        // Difficulty level.
        register_taxonomy(
            'lms_difficulty',
            'lms_course',
            array(
                'labels'            => array(
                    'name'              => _x( 'Difficulty Levels', 'taxonomy general name', 'unstoppable-lms' ),
                    'singular_name'     => _x( 'Difficulty Level', 'taxonomy singular name', 'unstoppable-lms' ),
                    'search_items'      => __( 'Search Difficulty Levels', 'unstoppable-lms' ),
                    'all_items'         => __( 'All Difficulty Levels', 'unstoppable-lms' ),
                    'edit_item'         => __( 'Edit Difficulty Level', 'unstoppable-lms' ),
                    'update_item'       => __( 'Update Difficulty Level', 'unstoppable-lms' ),
                    'add_new_item'      => __( 'Add New Difficulty Level', 'unstoppable-lms' ),
                    'new_item_name'     => __( 'New Difficulty Level Name', 'unstoppable-lms' ),
                    'menu_name'         => __( 'Difficulty', 'unstoppable-lms' ),
                ),
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'difficulty' ),
                'show_in_rest'      => true,
                'rest_base'         => 'lms-difficulty',
            )
        );
    }

    /**
     * Get list of registered post types.
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_post_types() {
        return array(
            'lms_course',
            'lms_lesson',
            'lms_topic',
            'lms_quiz',
            'lms_certificate',
        );
    }

    /**
     * Get list of registered taxonomies.
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_taxonomies() {
        return array(
            'lms_course_category',
            'lms_course_tag',
            'lms_difficulty',
        );
    }
}
