<?php
 /**
 * Plugin Name: Templatise Plugin
 * Description: This is a rough proof of concept to show how to use Custom Post types
 *
 * This plugin is part of a spike to explore using Custom Post types
 * It is a hodge podge of various posts on the subject.
 * Included is:
 *  Second WYSIWYG editor as a custom field
 *  Saving and retrieving data from the custom field
 *  Associating custom field data with revisions
 *
 * Shortcomings of this
 *  A revision is only created if the main content is updated
 */

function create_posttype() {
    register_post_type( 'news',
        array(
            'labels' => array(
                'name' => __( 'News' ),
                'singular_name' => __( 'News' ),
                'add_new_item' => __('Add News'),
            ),
            'taxonomies'  => array( 'category' ),
            'public' => true,
            'has_archive' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
            'rewrite' => array( 'slug' => '/', 'with_front' => false),
        )
    );
}




//  https://kellenmace.com/remove-custom-post-type-slug-from-permalinks/
/**
 * Have WordPress match postname to any of our public post types (post, page, review).
 * All of our public post types can have /post-name/ as the slug, so they need to be unique across all posts.
 * By default, WordPress only accounts for posts and pages where the slug is /post-name/.
 *
 * @param $query The current query.
 */
function gp_add_cpt_post_names_to_main_query( $query ) {
	// Bail if this is not the main query.
	if ( ! $query->is_main_query() ) {
		return;
	}
	// Bail if this query doesn't match our very specific rewrite rule.
	if ( ! isset( $query->query['page'] ) || 2 !== count( $query->query ) ) {
		return;
	}
	// Bail if we're not querying based on the post name.
	if ( empty( $query->query['name'] ) ) {
		return;
	}
	// Add CPT to the list of post types WP will include when it queries based on the post name.
	$query->set( 'post_type', array( 'post', 'page', 'news' ) );
}
add_action( 'pre_get_posts', 'gp_add_cpt_post_names_to_main_query' );

/**
 * Remove the slug from published post permalinks. Only affect our custom post type, though.
 */
function gp_remove_cpt_slug( $post_link, $post, $leavename ) {

    if ( 'news' != $post->post_type || 'publish' != $post->post_status ) {
        return $post_link;
    }

    $post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

    return $post_link;
}
add_filter( 'post_type_link', 'gp_remove_cpt_slug', 10, 3 );

// Hook into Wordpress
add_action('init', 'create_posttype');

// see https://www.sitepoint.com/wordpress-pages-use-tags/
// add tag support to pages
function tags_support_all() {
	register_taxonomy_for_object_type('post_tag', 'page');
	register_taxonomy_for_object_type('post_tag', 'news');
}

// ensure all tags are included in queries
function tags_support_query($wp_query) {
	if ($wp_query->get('tag')) $wp_query->set('post_type', 'any');
}

// tag hooks
add_action('init', 'tags_support_all');
add_action('pre_get_posts', 'tags_support_query');



function add_news_tag_taxonomy_to_post(){

    //set the name of the taxonomy
    $taxonomy = 'news_tag';

    //populate our array of names for our taxonomy
    $labels = array(
        'name'               => 'News Tags',
        'singular_name'      => 'News Tag',
        'search_items'       => 'Search News Tags',
        'all_items'          => 'All News Tags',
        'update_item'        => 'Update News Tag',
        'edit_item'          => 'Edit News Tag',
        'add_new_item'       => 'Add New News Tag',
        'new_item_name'      => 'New News Tag',
        'menu_name'          => 'News Tags'
    );

    //define arguments to be used
    $args = array(
        'labels'            => $labels,
        'hierarchical'      => false,
        'show_ui'           => true,
        'how_in_nav_menus'  => true,
        'public'            => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => false
    );

    //call the register_taxonomy function
    register_taxonomy($taxonomy, ['post', 'page', 'news'], $args);
}
add_action('init','add_news_tag_taxonomy_to_post');


if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_news',
		'title' => 'News',
		'fields' => array (
			array (
				'key' => 'field_5a0abc8b09319',
				'label' => 'Sub Title',
				'name' => 'sub_title',
				'type' => 'text',
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'formatting' => 'html',
				'maxlength' => '',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'news',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'acf_after_title',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
}
