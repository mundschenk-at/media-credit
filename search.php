<?php
if ( isset( $_GET['term'] ) ) {
	$dir = "../../..";
	require_once("$dir/wp-config.php");

	if ($authors = get_editable_authors_by_name( $current_user->id, $_GET['term'] ) ) {
		foreach ( $authors as $author )
			$results[] = (object) array("id"=>$author->ID, "label"=>$author->display_name, "value"=>$author->display_name);
		echo json_encode($results);
	}
	echo '';
}

/**
 * Returns the users that are editable by $user_id (normally the current user) and that contain $name within their display name. Important to use this function rather than just selected all users for WPMU bloggers.
 *
 * Basis for this function is proudly stolen from wp-admin/includes/user.php
 */
function get_editable_authors_by_name( $user_id, $name ) {
	global $wpdb;

	$editable = get_editable_user_ids( $user_id );

	if ( !$editable ) {
		return false;
	} else {
		$editable = join(',', $editable);
		// Prepare autocomplete term for query: add wildcard after, and replace all spaces with wildcards
		$name = str_replace( ' ', '%', $name ) . '%';
		$authors = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, display_name
			FROM $wpdb->users
			WHERE ID IN ($editable)
				AND upper(display_name) LIKE %s
			ORDER BY display_name",
			strtoupper($name) ));
	}

	return apply_filters('get_editable_authors_by_name', $authors, $name);
}

?>
