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
 register_post_type( 'review',
   array(
     'labels' => array(
       'name' => __( 'Reviews' ),
       'singular_name' => __( 'Review' ),
       'add_new_item' => __('Add new review'),
     ),
     'public' => true,
     'has_archive' => true,
     'rewrite' => array('slug' => 'reviews'),
     'supports' => array( 'title', 'editor', 'thumbnail', 'revisions', )
   )
 );
}

// Set up the editor
function templatise_aside_callback() {
   global $post;

   // get value of our custom field
   $first_field = get_post_meta($post->ID, 'templatise_aside', true);

   // create a nonce for secure saving
   wp_nonce_field( 'templatise_nonce', 'templatise_nonce' );

   // check if our custom field has content
   if( $first_field ) {
       // if it has content, set the $content so we can display it as value in the field
       $content = $first_field;
   } else {
       // if it has no content, just return an empty value
       $content = '';
   }

   // create a new instance of the WYSIWYG editor
   wp_editor( $content, 'templatise_aside_editor' , array(
       'wpautop'       => true,
       'textarea_name' => 'templatise_aside', // this is the 'name' attribute of our field
       'textarea_rows' => 10,
   ));
}

// initialise the meta box for adding to the new post type
function templatise_add_metabox() {
  add_meta_box(
      'review_aside', // ID of the metabox
      'Aside', // title of the metabox
      'templatise_aside_callback', // callback function, see below
      'review', // <--- your post-type slug
      'normal', // context
      'default' // priority
  );
}

// save post handles custom field data and revision data
function templatise_save_post($post_id) {
    // check our nonce
    if ( ! isset( $_POST['templatise_nonce'] ) ||
    ! wp_verify_nonce( $_POST['templatise_nonce'], 'templatise_nonce' ) )
        return;

    // check for autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // check if user has rights
    if (!current_user_can('edit_post', $post_id))
        return;

    // check if content exists in our custom field
    if ( isset( $_POST['templatise_aside'] ) ) {
        $contents = $_POST['templatise_aside'];

        // if content exists than update the meta value
        update_post_meta( $post_id, 'templatise_aside', $contents );
    }

    // save for revision purposes
    // modified from https://johnblackbourn.com/post-meta-revisions-wordpress/
    $parent_id = wp_is_post_revision( $post_id );

  	if ($parent_id) {
  		$parent  = get_post( $parent_id );
  		$my_meta = get_post_meta( $parent->ID, 'templatise_aside', true );
  		if ( false !== $my_meta ) {
  			add_metadata( 'post', $post_id, 'templatise_aside', $my_meta );
      }
  	}
}

// restore revision
// modified from https://johnblackbourn.com/post-meta-revisions-wordpress/
function templatise_restore_revision( $post_id, $revision_id ) {
	$post     = get_post( $post_id );
	$revision = get_post( $revision_id );
	$my_meta  = get_metadata( 'post', $revision->ID, 'templatise_aside', true );

	if ( false !== $my_meta ) {
		update_post_meta( $post_id, 'templatise_aside', $my_meta );
	} else {
		delete_post_meta( $post_id, 'templatise_aside' );
  }
}


// review revision
// modified from https://johnblackbourn.com/post-meta-revisions-wordpress/
function templatise_revision_fields( $fields ) {
	$fields['templatise_aside'] = 'Aside';
	return $fields;
}

// review revision
// modified from https://johnblackbourn.com/post-meta-revisions-wordpress/
function templatise_revision_field( $value, $field ) {
	global $revision;
	return get_metadata( 'post', $revision->ID, $field, true );
}


// Hook into Wordpress
add_action('admin_init', 'templatise_add_metabox', 1);
add_action('init', 'create_posttype');
add_action('save_post', 'templatise_save_post');
add_action('wp_restore_post_revision', 'templatise_restore_revision', 10, 2);
add_filter('_wp_post_revision_fields', 'templatise_revision_fields' );
add_filter('_wp_post_revision_field_my_meta', 'templatise_revision_field', 10, 2);
