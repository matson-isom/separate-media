<?php
/*
Plugin Name:  Separate Media
Plugin URI:   http://URI_Of_Page_Describing_Plugin_and_Updates
Description:  Separate user's media by user or role.
Version:      1.0.0
Author:       Matson & Isom Technology
Author URI:   http://www.mitcs.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

if (is_admin()) {
  require_once(dirname(__file__).'/admin/options_menu.php');
}

/**
 * Fires after the main query vars have been parsed.
 *
 * @since 1.5.0
 */
add_action('parse_query', 'sm_restrict_media_access');
/**
 * Restrict the current user's access to Media items according to settings.
 */
function sm_restrict_media_access() {
  if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/upload.php' ) !== false ) {
    // Don't restrict access for Administrators
    if (!current_user_can('administrator')) {
      set_query_var('author', sm_get_authors());
    }
  }
}

/**
 * Get comma separated string of authors (IDs) who the current user is allowed to see the uploads of.
 */
function sm_get_authors() {
  $authors = array();
  $separation_method = array_shift(get_option('sm_separation_method'));
  switch ($separation_method) {
    // Users see their files and no one elses.
    case 'user':
      $me = wp_get_current_user();
      $authors[] = $me->ID;
      break;

    // Users must share at least one role to see each others uploads
    case 'role_union':
      $me = wp_get_current_user();
      $my_roles = $me->roles;
      $users_who_share_a_role_with_me = array();

      // Gather all users who share at least one of the current user's roles
      foreach ($my_roles as $my_role) {
        $q = new WP_User_Query(array('role' => $my_role));
        $users_with_my_role = $q->get_results();

        foreach ($users_with_my_role as $user_with_my_role) {
          $authors[] = $user_with_my_role->ID;
        }
        unset($q);
      }
      break;

    // Users must share at least all of the current user's roles.
    case 'role_loose_intersect':
    // Users must have exactly the same roles to see each others uploads.
    case 'role_strict_intersect':
      global $wpdb;
      $blog_id = get_current_blog_id();
      $me = wp_get_current_user();
      $my_roles = $me->roles;

      // Gather all users who have all of the current users's roles. This
      // includeds users who have the current user's roles plus additional
      // roles.
      $mq_args = array('relation' => 'AND');
      foreach ($my_roles as $role) {
        $mq_args[] = array(
          'key' => $wpdb->get_blog_prefix($blog_id).'capabilities',
          'value' => $role,
          'compare' => 'like'
        );
      }
      $q = new WP_User_Query(array('meta_query' => $mq_args));
      $users_with_my_roles = $q->get_results();
      unset($q);

      // Strict intersect requires users to have the current user's roles and
      // no additional roles.
      $users_with_only_my_roles = array();
      if ($separation_method == "role_strict_intersect") {
        foreach ($users_with_my_roles as $user) {
          if (!array_diff($user->roles, $my_roles))) {
            $authors[] = $user->ID;
          }
        }
      }
      else {
        foreach ($users_with_my_roles as $user) {
          $authors[] = $user->ID;
        }
      }
      break;
  }

  return implode(",", array_unique($authors));
}

/**
 * Ajax handler for querying attachments.
 *
 * @since 3.5.0
 */
add_action('wp_ajax_query-attachments', 'sm_wp_ajax_query_attachments', 1);
function sm_wp_ajax_query_attachments() {
  if ( ! current_user_can( 'upload_files' ) )
    wp_send_json_error();

  $query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
  $keys = array(
    's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
    'post_parent', 'post__in', 'post__not_in', 'year', 'monthnum'
  );
  foreach ( get_taxonomies_for_attachments( 'objects' ) as $t ) {
    if ( $t->query_var && isset( $query[ $t->query_var ] ) ) {
      $keys[] = $t->query_var;
    }
  }

  $query = array_intersect_key( $query, array_flip( $keys ) );
  $query['post_type'] = 'attachment';
  if ( MEDIA_TRASH
    && ! empty( $_REQUEST['query']['post_status'] )
    && 'trash' === $_REQUEST['query']['post_status'] ) {
    $query['post_status'] = 'trash';
  } else {
    $query['post_status'] = 'inherit';
  }

  if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) )
    $query['post_status'] .= ',private';

  /**
   * Filter the arguments passed to WP_Query during an AJAX
   * call for querying attachments.
   *
   * @since 3.7.0
   *
   * @see WP_Query::parse_query()
   *
   * @param array $query An array of query variables.
   */
  // Don't restrict access for Administrators
  if (!current_user_can('administrator')) {
    $query['author'] = sm_get_authors();
  }
  $query = apply_filters( 'ajax_query_attachments_args', $query );
  $query = new WP_Query( $query );

  $posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
  $posts = array_filter( $posts );

  wp_send_json_success( $posts );
}

?>
