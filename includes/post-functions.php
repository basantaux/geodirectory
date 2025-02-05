<?php
/**
 * Post Listing functions.
 *
 * @since 1.0.0
 * @package GeoDirectory
 */

/**
 * Get post custom fields.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 * @global object $post The current post object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param int|string $post_id Optional. The post ID.
 *
 * @return object|bool Returns full post details as an object. If no details returns false.
 */
function geodir_get_post_info( $post_id = '' ) {
	// check for cache
	$cache = wp_cache_get( "gd_post_" . $post_id, 'gd_post' );
	if ( $cache ) {
		return $cache;
	}

	global $wpdb, $plugin_prefix, $post, $post_info, $preview;

	if ( $post_id == '' && ! empty( $post ) ) {
		$post_id = $post->ID;
	}

	$post_type = get_post_type( $post_id );

	if ( $post_type == 'revision' ) {
		$post_type = get_post_type( wp_get_post_parent_id( $post_id ) );
	}

	// check if preview
	if ( $preview && $post->ID == $post_id ) {
		$post_id = GeoDir_Post_Data::get_post_preview_id( $post_id );
	}

	$all_postypes = geodir_get_posttypes();

	if ( ! in_array( $post_type, $all_postypes ) ) {
		return new stdClass();
	}

	$table = $plugin_prefix . $post_type . '_detail';

	/**
	 * Apply Filter to change Post info
	 *
	 * You can use this filter to change Post info.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$query = apply_filters( 'geodir_post_info_query', $wpdb->prepare( "SELECT p.*,pd.* FROM " . $wpdb->posts . " p," . $table . " pd
			  WHERE p.ID = pd.post_id
			  AND pd.post_id = %d", $post_id ) );

	$post_detail = $wpdb->get_row( $query );

	// check for distance setting
	if ( ! empty( $post_detail ) && ! empty( $post->distance ) ) {
		$post_detail->distance = $post->distance;
	}

	$return = ( ! empty( $post_detail ) ) ? $post_info = $post_detail : $post_info = false;

	// set cache
	if ( ! empty( $post_detail ) ) {
		wp_cache_set( "gd_post_" . $post_id, $post_detail, 'gd_post' );
	}

	return $return;

}

/**
 * Save or update post custom fields.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param int $post_id The post ID.
 * @param string $postmeta Detail table column name.
 * @param string $meta_value Detail table column value.
 *
 * @return void|bool
 */
function geodir_save_post_meta( $post_id, $postmeta = '', $meta_value = '' ) {

	global $wpdb, $plugin_prefix;

	$post_type = get_post_type( $post_id );

	$table = $plugin_prefix . $post_type . '_detail';

	if ( $postmeta != '' && geodir_column_exist( $table, $postmeta ) && $post_id ) {

		if ( is_array( $meta_value ) ) {
			$meta_value = implode( ",", $meta_value );
		}

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT post_id from " . $table . " where post_id = %d", array( $post_id ) ) ) ) {

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . $table . " SET " . $postmeta . " = '" . $meta_value . "' where post_id =%d",
					array( $post_id )
				)
			);

		} else {

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO " . $table . " SET post_id = %d, " . $postmeta . " = '" . $meta_value . "'",
					array( $post_id )
				)
			);
		}

		// clear the post cache
		wp_cache_delete( "gd_post_" . $post_id, 'gd_post' );

	} else {
		return false;
	}
}


/**
 * Delete post custom fields.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param int $post_id The post ID.
 * @param string $postmeta Detail table column name.
 *
 * @return bool
 */
function geodir_delete_post_meta( $post_id, $postmeta ) {

	global $wpdb, $plugin_prefix;

	$post_type = get_post_type( $post_id );

	$table = $plugin_prefix . $post_type . '_detail';

	// clear the post cache
	wp_cache_delete( "gd_post_" . $post_id, 'gd_post' );

	if ( is_array( $postmeta ) && ! empty( $postmeta ) && $post_id ) {
		$post_meta_set_query = '';

		foreach ( $postmeta as $mkey ) {
			if ( $mkey != '' ) {
				$post_meta_set_query .= $mkey . " = '', ";
			}
		}

		$post_meta_set_query = trim( $post_meta_set_query, ", " );

		if ( empty( $post_meta_set_query ) || trim( $post_meta_set_query ) == '' ) {
			return false;
		}

		if ( $wpdb->get_var( "SHOW COLUMNS FROM " . $table . " WHERE field = '" . $postmeta . "'" ) != '' ) {

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . $table . " SET " . $post_meta_set_query . " where post_id = %d",
					array( $post_id )
				)
			);

			return true;
		}

	} elseif ( $postmeta != '' && $post_id ) {
		if ( $wpdb->get_var( "SHOW COLUMNS FROM " . $table . " WHERE field = '" . $postmeta . "'" ) != '' ) {

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE " . $table . " SET " . $postmeta . "= '' where post_id = %d",
					array( $post_id )
				)
			);

			return true;
		}

	} else {
		return false;
	}
}


/**
 * Get post custom meta.
 *
 * @since 1.0.0
 * @since 1.6.20 Hook added to filter value.
 * @package GeoDirectory
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param int $post_id The post ID.
 * @param string $meta_key The meta key to retrieve.
 * @param bool $single Optional. Whether to return a single value. Default false.
 *
 * @todo single variable not yet implemented.
 * @return bool|mixed|null|string Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
if ( ! function_exists( 'geodir_get_post_meta' ) ) {
function geodir_get_post_meta( $post_id, $meta_key, $single = false ) {
	if ( ! $post_id ) {
		return false;
	}
	global $wpdb, $plugin_prefix, $preview;

	$all_postypes = geodir_get_posttypes();

	$post_type = get_post_type( $post_id );

	if ( $post_type == 'revision' ) {
		$post_type = get_post_type( wp_get_post_parent_id( $post_id ) );
	}

	// check if preview
	if ( $preview ) {
		$post_id = GeoDir_Post_Data::get_post_preview_id( $post_id );
	}

	if ( ! in_array( $post_type, $all_postypes ) ) {
		return false;
	}

	/**
	 * Short circuit the DB query if needed.
	 */
	$pre_value = apply_filters( 'geodir_pre_get_post_meta', null, $post_id, $meta_key, $single );
	if($pre_value!==null){
		return $pre_value;
	}

	$table = $plugin_prefix . $post_type . '_detail';

	if ( $table && $meta_key ) {
		//if ( $wpdb->get_var( "SHOW COLUMNS FROM " . $table . " WHERE field = '" . $meta_key . "'" ) != '' ) {
		$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT `" . $meta_key . "` from " . $table . " where post_id = %d", array( $post_id ) ) );

		if ( ($meta_value || $meta_value==='0') && $meta_value !== '' ) {
			$meta_value = maybe_serialize( $meta_value );
		}else{
			$meta_value = false;
		}
	} else {
		$meta_value = false;
	}

	/**
	 * Filter the listing custom meta.
	 *
	 * @since 1.6.20
	 *
	 * @param bool|mixed|null|string $meta_value Will be an array if $single is false. Will be value of meta data field if $single is true.
	 * @param int $post_id The post ID.
	 * @param string $meta_key The meta key to retrieve.
	 * @param bool $single Optional. Whether to return a single value. Default false.
	 */
	return apply_filters( 'geodir_get_post_meta', $meta_value, $post_id, $meta_key, $single );
}
}

/**
 * Checks if a given post has a given custom meta.
 *
 * @since 2.0.0.65
 * @package GeoDirectory
 * @global object $post Current post object.
 *
 * @param string $meta_key The meta key to check.
 * @param int $post_id Optional. Defaults to current post. The post whose meta should be checked.
 *
 * @return bool True if the key exists and has a non-empty value. False otherwise.
 */
if ( ! function_exists( 'geodir_has_post_meta' ) ) {
	function geodir_has_post_meta( $meta_key, $post_id = null ) {
		global $post;

		//Use the current post's id if none is provided
		if( is_null( $post_id ) ) {
			$post_id = $post->ID;
		}

		return !empty( geodir_get_post_meta( $post_id, $meta_key, true ) );

	}
}

/**
 * Default post status for new posts.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @return string Returns the default post status for new posts. Ex: draft, publish etc.
 */
function geodir_new_post_default_status() {
	return GeoDir_Post_Data::get_post_default_status();
}

/**
 * This function would display the html content for add to favorite or remove from favorite.
 *
 * @since 1.0.0
 * @package GeoDirectory
 *
 * @global object $current_user Current user object.
 * @global object $post The current post object.
 *
 * @param int $user_id User id.
 * @param int $post_id Post id.
 *
 * @return string
 */
function geodir_favourite_html( $user_id, $post_id ) {

	global $current_user, $post;

	if ( isset( $post->post_type ) && $post->post_type ) {
		$post_type = $post->post_type;
	} else {
		$post_type = get_post_type( $post_id );
	}

	if ( geodir_cpt_has_favourite_disabled( $post_type ) ) {
		return '';
	}

	/**
	 * Filter to modify "Add to Favorites" text
	 *
	 * You can use this filter to rename "Add to Favorites" text to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$add_favourite_text = apply_filters( 'geodir_add_favourite_text', ADD_FAVOURITE_TEXT );

	/**
	 * Filter to modify "Favourite" text
	 *
	 * You can use this filter to rename "Favourite" text to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$favourite_text = apply_filters( 'geodir_favourite_text', FAVOURITE_TEXT );

	/**
	 * Filter to modify "Unfavorite" text
	 *
	 * You can use this filter to rename "Unfavorite" text to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$remove_favourite_text = apply_filters( 'geodir_remove_favourite_text', REMOVE_FAVOURITE_TEXT );

	/**
	 * Filter to modify "Remove from Favorites" text
	 *
	 * You can use this filter to rename "Remove from Favorites" text to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$unfavourite_text = apply_filters( 'geodir_unfavourite_text', UNFAVOURITE_TEXT );

	/**
	 * Filter to modify "fas fa-heart" icon
	 *
	 * You can use this filter to change "fas fa-heart" icon to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$favourite_icon = apply_filters( 'geodir_favourite_icon', 'fas fa-heart' );

	/**
	 * Filter to modify "fas fa-heart" icon for "remove from favorites" link
	 *
	 * You can use this filter to change "fas fa-heart" icon to something else.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	$unfavourite_icon = apply_filters( 'geodir_unfavourite_icon', 'fas fa-heart' );

//    echo get_current_user_id().'###';print_r($current_user);

	$user_meta_data = '';
	if ( isset( $current_user->data->ID ) ) {
		$user_meta_data = geodir_get_user_favourites( $current_user->data->ID );
	}

	if ( ! empty( $user_meta_data ) && in_array( $post_id, $user_meta_data ) ) {
		?><span class="geodir-addtofav favorite_property_<?php echo $post_id; ?>"  ><a
			class="geodir-removetofav-icon" href="javascript:void(0);"
			onclick="javascript:gd_fav_save(<?php echo $post_id; ?>);"
			title="<?php echo $remove_favourite_text; ?>"><i
				class="<?php echo $unfavourite_icon; ?>"></i> <span class="geodir-fav-text"><?php echo $unfavourite_text; ?></span></a>   </span><?php

	} else {

		if ( ! isset( $current_user->data->ID ) || $current_user->data->ID == '' ) {
			$script_text = 'javascript:window.location.href=\'' . geodir_login_url() . '\'';
		} else {
			$script_text = 'javascript:gd_fav_save(' . $post_id . ')';
		}

		?><span class="geodir-addtofav favorite_property_<?php echo $post_id; ?>"><a class="geodir-addtofav-icon"
		                                                                             href="javascript:void(0);"
		                                                                             onclick="<?php echo $script_text; ?>"
		                                                                             title="<?php echo $add_favourite_text; ?>"><i
				class="<?php echo $favourite_icon; ?>"></i> <span class="geodir-fav-text"><?php echo $favourite_text; ?></span></a></span>
	<?php }
}

/**
 * Get listing author id.
 *
 * @since 1.0.0
 * @package GeoDirectory
 *
 * @param int|string $listing_id The post ID.
 *
 * @return string|int The author ID.
 */
function geodir_get_listing_author( $listing_id = '' ) {
	if ( $listing_id == '' ) {
		if ( isset( $_REQUEST['pid'] ) && $_REQUEST['pid'] != '' ) {
			$listing_id = $_REQUEST['pid'];
		}
	}
	$listing           = get_post( strip_tags( $listing_id ) );
	$listing_author_id = $listing->post_author;

	return $listing_author_id;
}


/**
 * Check whether a listing belongs to a user or not.
 *
 * @since 1.0.0
 * @package GeoDirectory
 *
 * @param int|string $listing_id The post ID.
 * @param int $user_id The user ID.
 *
 * @return bool
 */
function geodir_lisiting_belong_to_user( $listing_id, $user_id ) {
	if ( empty( $listing_id ) || empty( $user_id ) ) {
		return false;
	}

	$listing_author_id = geodir_get_listing_author( $listing_id );

	if ( $listing_author_id == $user_id ) {
		return true;
	} else {
		return false;
	}

}

/**
 * Check whether a listing belongs to current user or not.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global object $current_user Current user object.
 *
 * @param int|string $listing_id The post ID.
 * @param bool $exclude_admin Optional. Do you want to exclude admin from the check?. Default true.
 *
 * @return bool
 */
function geodir_listing_belong_to_current_user( $listing_id = '', $exclude_admin = true ) {
	global $current_user;
	if ( $exclude_admin ) {
		foreach ( $current_user->caps as $key => $caps ) {
			if ( geodir_strtolower( $key ) == 'administrator' ) {
				return true;
				break;
			}
		}
	}

	$belong_to_user = geodir_lisiting_belong_to_user( $listing_id, $current_user->ID );

	/**
	 * Filter whether a listing belongs to current user or not.
	 *
	 * @since 2.0.0.65
	 *
	 * @param bool $belong_to_user True if a listing belongs to current user or False.
	 * @param int|string $listing_id The post ID.
	 * @param bool $exclude_admin If True it excludes admin from the check.
	 */
	return apply_filters( 'geodir_listing_belong_to_current_user', $belong_to_user, $listing_id, $exclude_admin );
}

/**
 * Called when post updated.
 *
 * @since 1.0.0
 * @package GeoDirectory
 *
 * @param int $post_ID The post ID.
 * @param object $post_after The post object after update.
 * @param object $post_before The post object before update.
 */
function geodir_function_post_updated( $post_ID, $post_after, $post_before ) {
	$post_type = get_post_type( $post_ID );

	if ( $post_type != '' && in_array( $post_type, geodir_get_posttypes() ) ) {
		// send notification to client when post moves from draft to publish
		if ( ! empty( $post_after->post_status ) && $post_after->post_status == 'publish' && ! empty( $post_before->post_status ) && $post_before->post_status != 'publish' && $post_before->post_status != 'trash' ) {
			$gd_post = geodir_get_post_info( $post_ID );
			if ( empty( $gd_post ) ) {
				return;
			}
			// Send email to user
			GeoDir_Email::send_user_publish_post_email( $gd_post );
		}
	}
}
add_action( 'post_updated', 'geodir_function_post_updated', 16, 3 );

/**
 * Adds the featured image to the place details page header so facebook can use it when sharing the link.
 *
 * @since 1.4.9
 * @package GeoDirectory
 */
function geodir_fb_like_thumbnail() {

	// return if not a single post
	if ( ! is_single() ) {
		return;
	}

	global $gd_post;
	if ( isset( $gd_post->featured_image ) && $gd_post->featured_image ) {
		$upload_dir = wp_upload_dir();
		$thumb      = $upload_dir['baseurl'] . $gd_post->featured_image;
		echo "\n\n<!-- GD Facebook Like Thumbnail -->\n<link rel=\"image_src\" href=\"$thumb\" />\n<!-- End GD Facebook Like Thumbnail -->\n\n";

	}
}
add_action( 'wp_head', 'geodir_fb_like_thumbnail' );

/**
 * Get custom statuses.
 *
 * @since 2.0.0
 *
 * @return array $custom_statuses.
 */
function geodir_get_custom_statuses() {
	$custom_statuses = array(
		'gd-closed' => _x( 'Closed down', 'Listing status', 'geodirectory' )
	);

	return apply_filters( 'geodir_listing_custom_statuses', $custom_statuses );
}

/**
 * Get post statuses.
 *
 * @since 2.0.0
 *
 * @return array $statuses.
 */
function geodir_get_post_statuses() {
	$default_statuses = get_post_statuses();
	$custom_statuses  = geodir_get_custom_statuses();

	$statuses = array_merge( $default_statuses, $custom_statuses );

	return apply_filters( 'geodir_post_statuses', $statuses );
}

/**
 * Get the nice name for an listing status.
 *
 * @since  2.0.0
 *
 * @param  string $status
 *
 * @return string
 */
function geodir_get_post_status_name( $status ) {
	$statuses = geodir_get_post_statuses();
	if ( ! empty( $statuses ) && isset( $statuses[ $status ] ) ) {
		$status_name = $statuses[ $status ];
	} else {
		$status_object = get_post_status_object( $status );
		if ( ! empty( $status_object->label ) ) {
			$status_name = $status_object->label;
		} else {
			$status_name = $status;
		}
	}

	return $status_name;
}

/**
 * Post is closed.
 *
 * @since 2.0.0
 *
 * @param object $post Post object.
 *
 * @return bool $closed
 */
function geodir_post_is_closed( $post ) {
	if ( empty( $post ) ) {
		return false;
	}

	$status = ! empty( $post->post_status ) ? $post->post_status : get_post_status( $post );
	$closed = $status == 'gd-closed' ? true : false;

	return apply_filters( 'geodir_post_is_closed', $closed, $post );
}


/**
 * Returns the edit post link.
 *
 * @since 2.0.0
 * @package GeoDirectory
 */
function geodir_edit_post_link( $post_id = '' ) {

	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$postlink = get_permalink( geodir_add_listing_page_id() );

	return geodir_getlink( $postlink, array( 'pid' => $post_id ), false );

}

/**
 * Setup $gd_post variable.
 *
 * @since 2.0.0
 *
 * @param int $the_post the post.
 */
function geodir_setup_postdata( $the_post ) {
	global $post;

	if ( is_int( $the_post ) && $the_post > 0 ) {
		$the_post = geodir_get_post_info( $the_post );
	} else if ( is_object( $the_post ) ) {
		if ( ! isset( $the_post->post_category ) ) {
			$post_id  = isset( $the_post->ID ) ? $the_post->ID : $post->ID;
			$the_post = geodir_get_post_info( $post_id );
		}
	}

	if ( empty( $the_post->ID ) ) {
		return;
	}

	$GLOBALS['gd_post'] = $the_post;

	if ( empty( $post ) ) {
		$post = get_post( $the_post->ID );
		setup_postdata( $post );
		$GLOBALS['post'] = $post;
	} else if ( ! empty( $post ) && $post->ID != $the_post->ID ) {
		setup_postdata( $the_post->ID );
		if ( $post->ID != $the_post->ID ) {
			$GLOBALS['post'] = get_post( $the_post->ID );
		}
	}
}

/**
 * Get post badge.
 *
 * @since 2.0.0
 *
 * @param int $post_id Post id/
 * @param array $args Optional. Post arguments. Default array.
 *
 * @global object $gd_post GD post object.
 *
 * @return string $output.
 */
function geodir_get_post_badge( $post_id ='', $args = array() ) {
	global $gd_post;

//	print_r( $args );

	$output = '';
	if ( empty( $post_id ) ) {
		//return $output;
	}

	$post_type = $post_id ? get_post_type( $post_id ) : '';

	// check if its demo content
	if ($post_type &&  $post_type == 'page' && geodir_is_block_demo() ) {
		$post_type = 'gd_place';
	}

	if ($post_type &&  ! geodir_is_gd_post_type( $post_type ) ) {
		return $output;
	}

	$defaults = array(
		'key'       => '',
		'condition' => '',
		'search'    => 'is_equal',
		'badge'     => '',
		'link'     => '',
		'new_window'     => '',
		'bg_color'  => '#0073aa',
		'txt_color' => '#ffffff',
		'size'      => '',
		'alignment' => '',
		'css_class' => '',
		'onclick'   => '',
		'icon_class'=> '',
		'extra_attributes'=> '' // 'data-save-list-id=123 data-other-post-id=321'
	);
	$args     = shortcode_atts( $defaults, $args, 'gd_post_badge' );

	$match_field = $args['key'];
	if ( $match_field == 'address' ) {
		$match_field = 'street';
	}

	$find_post   = ! empty( $gd_post->ID ) && $gd_post->ID == $post_id ? $gd_post : geodir_get_post_info( $post_id );

	if ($match_field === '' || ( ! empty( $find_post ) && isset( $find_post->{$match_field} ) ) ) {
		$badge = $args['badge'];

		// Check if there is a specific filter for field.
		if ( has_filter( 'geodir_output_badge_field_key_' . $match_field ) ) {
			$output = apply_filters( 'geodir_output_badge_field_key_' . $match_field, $output, $find_post, $args );
		}

		if ($match_field && $match_field !== 'post_date' ) {
			$fields = geodir_post_custom_fields( '', 'all', $post_type, 'none' );

			$field = array();
			foreach ( $fields as $field_info ) {
				if ( $match_field == $field_info['htmlvar_name'] ) {
					$field = $field_info;
					break;
				} elseif( $match_field == 'street' && 'address' == $field_info['htmlvar_name'] ) {
					$field = $field_info;
					break;
				}
			}
			if ( ! empty( $field ) ) {


				// Check if there is a specific filter for key type.
				if ( has_filter( 'geodir_output_badge_key_' . $field['field_type_key'] ) ) {
					$output = apply_filters( 'geodir_output_badge_key_' . $field['field_type_key'], $output, $find_post, $args, $field );
				}

				// Check if there is a specific filter for condition.
				if ( has_filter( 'geodir_output_badge_condition_' . $args['condition'] ) ) {
					$output = apply_filters( 'geodir_output_badge_condition_' . $args['condition'], $output, $find_post, $args, $field );
				}
			} else {
				return $output;
			}
		}

		// If not then we run the standard output.
		if ( empty( $output ) ) {
			$search = $args['search'];

			$match_value = isset($find_post->{$match_field}) ? esc_attr( trim( $find_post->{$match_field} ) ) : ''; // escape user input
			$match_found = $match_field === '' ? true : false;

			if(!$match_found ){
				if ( $match_field == 'post_date' ) {
					if ( strpos( $search, '+' ) === false && strpos( $search, '-' ) === false ) {
						$search = '+' . $search;
					}
					$until_time = strtotime( get_the_time( 'Y-m-d' ) . ' ' . $search . ' days' );
					$now_time   = strtotime( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) );
					if ( $until_time >= $now_time ) {
						$match_found = true;
					}
				}
				else {
					switch ( $args['condition'] ) {
						case 'is_equal':
							$match_found = (bool) ( $search != '' && $match_value == $search );
							break;
						case 'is_not_equal':
							$match_found = (bool) ( $search != '' && $match_value != $search );
							break;
						case 'is_greater_than':
							$match_found = (bool) ( $search != '' && is_float( $search ) && is_float( $match_value ) && $match_value > $search );
							break;
						case 'is_less_than':
							$match_found = (bool) ( $search != '' && is_float( $search ) && is_float( $match_value ) && $match_value < $search );
							break;
						case 'is_empty':
							$match_found = (bool) ( $match_value === '' || $match_value === false || $match_value === '0' || is_null( $match_value ) );
							break;
						case 'is_not_empty':
							$match_found = (bool) ( $match_value !== '' && $match_value !== false && $match_value !== '0' && ! is_null( $match_value ) );
							break;
						case 'is_contains':
							$match_found = (bool) ( $search != '' && stripos( $match_value, $search ) !== false );
							break;
						case 'is_not_contains':
							$match_found = (bool) ( $search != '' && stripos( $match_value, $search ) === false );
							break;
					}
				}
			}

			/**
			 * @since 2.0.0.67
			 */
			$match_found = apply_filters( 'geodir_post_badge_check_match_found', $match_found, $args, $find_post );

			if ( $match_found ) {
				// check for price format
				if ( isset( $field['data_type'] ) && ( $field['data_type'] == 'INT' || $field['data_type'] == 'FLOAT' || $field['data_type'] == 'DECIMAL' ) && isset( $field['extra_fields'] ) && $field['extra_fields'] ) {
					$extra_fields = stripslashes_deep( maybe_unserialize( $field['extra_fields'] ) );

					if ( ! empty( $extra_fields ) && isset( $extra_fields['is_price'] ) && $extra_fields['is_price'] ) {
						if ( ceil( $match_value ) > 0 ) {
							$match_value = geodir_currency_format_number( $match_value, $field );
						}
					}
				}

				// badge text
				if ( empty( $badge ) && empty($args['icon_class']) ) {
					$badge = $field['frontend_title'];
				}
				if( !empty( $badge ) && $badge = str_replace("%%input%%",$match_value,$badge) ){
					// will be replace in condition check
				}
				if( !empty( $badge ) && $post_id && $badge = str_replace("%%post_url%%",get_permalink($post_id),$badge) ){
					// will be replace in condition check
				}

				//link url, replave vars
				if( !empty( $args['link'] ) && $args['link'] = str_replace("%%input%%",$match_value,$args['link']) ){
					// will be replace in condition check
				}
				if( !empty( $args['link'] ) && $post_id && $args['link'] = str_replace("%%post_url%%",get_permalink($post_id),$args['link']) ){
					// will be replace in condition check
				}

				if ( empty( $badge ) && $match_field == 'post_date' ) {
					$badge = __( 'NEW', 'geodirectory' );
				}

				// replace other post variables
				if(!empty($badge)){
					$badge = geodir_replace_variables($badge);
				}

				$class = '';
				if ( ! empty( $args['size'] ) ) {
					$class .= ' gd-badge-' . sanitize_title( $args['size'] );
				}
				if ( ! empty( $args['alignment'] ) ) {
					$class .= ' gd-badge-align' . sanitize_title($args['alignment']);
				}
				if ( ! empty( $args['css_class'] ) ) {
					$class .= ' ' . esc_attr($args['css_class']);
				}

				// new window
				$new_window = '';
				if ( ! empty( $args['new_window'] ) ) {
					$new_window = ' target="_blank" ';
				}

				// check if its exteranl it should be no follow
				$rel = '';
				if(!empty($args['link'])){
					$rel = strpos($args['link'], get_site_url()) !== false ? '' : 'rel="nofollow"';
				}

				// allow gd-lity class to enable lity lightbox on link
				if(!empty($args['link']) && !empty($args['css_class']) &&  strpos($args['css_class'], 'gd-lity') !== false){
					$rel .= ' data-lity ';
				}

				// onclick
				$onclick = '';
				if(!empty($args['onclick'])){
					$onclick = 'onclick="'.esc_attr($args['onclick']).'"';
				}

				// fontawesom icon
				$icon = '';
				if(!empty($args['icon_class'])){
					$icon = '<i class="'.esc_attr($args['icon_class']).'" ></i>';
				}

				// data-attributes
				$extra_attributes = '';
				if(!empty($args['extra_attributes'])){
					$extra_attributes = esc_attr( $args['extra_attributes'] );
					$extra_attributes = str_replace("&quot;",'"',$extra_attributes);
				}

//				echo $badge.'###';exit;

				// Maybe translate badge
				$badge = __( $badge, 'geodirectory' );

				// title
				$title = $badge ? $badge : '';
				if(!$title){$title = isset($field['frontend_title']) ? $field['frontend_title'] : '';}

				// set badge text as secondary if icon is set.
				if( $icon ){
					$badge = " <span class='gd-secondary'>$badge</span>";
				}

				// phone & email link
				if ( ! empty( $field ) && ! empty( $field['field_type'] ) && ! empty( $args['link'] ) && strpos( $args['link'], 'http' ) !== 0 ) {
					if ( $field['field_type'] == 'phone' ) {
						$rel = 'rel="nofollow"';
						if ( strpos( $args['link'], 'tel:' ) !== 0 ) {
							$args['link'] = 'tel:' . preg_replace( '/[^0-9+]/', '', $args['link'] );
						}
					} elseif ( $field['field_type'] == 'email' ) {
						$rel = 'rel="nofollow"';
						if ( strpos( $args['link'], 'mailto:' ) !== 0 ) {
							$args['link'] = 'mailto:' . $args['link'];
						}
					}
				}

				$output = '<div class="gd-badge-meta ' . trim( $class ) . ' gd-badge-meta-' . sanitize_title_with_dashes( esc_attr( $title ) ).'" '.$onclick.' '.$extra_attributes.' title="'.esc_attr( $title ).'">';
				if(!empty($args['link'])){$output .= "<a href='".esc_url($args['link'])."' $new_window $rel>";}
				$post_id = isset($find_post->ID) ? absint($find_post->ID) : '';
				// we escape the user input from $match_value but we don't escape the user badge input so they can use html like font awesome.
				$output .= '<div data-id="' . $post_id . '" class="gd-badge" data-badge="' . esc_attr($match_field) . '" data-badge-condition="' . esc_attr($args['condition']) . '" style="background-color:' . esc_attr( $args['bg_color'] ) . ';color:' . esc_attr( $args['txt_color'] ) . ';">' . $icon . $badge . '</div>';
				if(!empty($args['link'])){$output .= "</a>";}
				$output .= '</div>';
			}
		}
	}

	return $output;
}

/**
 * Filters the JOIN clause in the SQL for an adjacent post query.
 *
 * @since 1.6.16
 * @package GeoDirectory
 *
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param string $join The JOIN clause in the SQL.
 * @param bool $in_same_term Whether post should be in a same taxonomy term.
 * @param array $excluded_terms Array of excluded term IDs.
 * @param string $taxonomy Taxonomy. Used to identify the term used when `$in_same_term` is true.
 * @param WP_Post $post WP_Post object.
 *
 * @return string Filtered SQL JOIN clause.
 */
function geodir_previous_next_post_join( $join, $in_same_term, $excluded_terms, $taxonomy, $post ) {
	global $plugin_prefix;

	if ( ! empty( $post->post_type ) && in_array( $post->post_type, geodir_get_posttypes() ) ) {
		$join .= " INNER JOIN " . $plugin_prefix . $post->post_type . "_detail AS gd ON gd.post_id = p.ID";
	}

	return $join;
}

add_filter( 'get_previous_post_join', 'geodir_previous_next_post_join', 10, 5 );
add_filter( 'get_next_post_join', 'geodir_previous_next_post_join', 10, 5 );

/**
 * Filters the WHERE clause in the SQL for an adjacent post query.
 *
 * @since 1.6.16
 * @package GeoDirectory
 *
 * @global object $wpdb WordPress Database object.
 * @global string $plugin_prefix Geodirectory plugin table prefix.
 *
 * @param string $where The `WHERE` clause in the SQL.
 * @param bool $in_same_term Whether post should be in a same taxonomy term.
 * @param array $excluded_terms Array of excluded term IDs.
 * @param string $taxonomy Taxonomy. Used to identify the term used when `$in_same_term` is true.
 * @param WP_Post $post WP_Post object.
 *
 * @return string Filtered SQL WHERE clause.
 */
function geodir_previous_next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
	global $wpdb, $plugin_prefix;

	if ( ! empty( $post->post_type ) && ( ! empty( $post->country_slug ) || ! empty( $post->region_slug ) || ! empty( $post->city_slug ) ) && in_array( $post->post_type, geodir_get_posttypes() ) ) {
		$post_locations     = '';
		$post_locations_var = array();

		if ( ! empty( $post->country_slug ) ) {
			$post_locations .= " AND post_locations LIKE %s";
			$post_locations_var[] = "%,[" . $post->country_slug . "]";
		}

		if ( ! empty( $post->region_slug ) ) {
			$post_locations .= " AND post_locations LIKE %s";
			$post_locations_var[] = "%,[" . $post->region_slug . "],%";
		}

		if ( ! empty( $post->city_slug ) ) {
			$post_locations .= " AND post_locations LIKE %s";
			$post_locations_var[] = "[" . $post->city_slug . "],%";
		}

		$where .= $wpdb->prepare( $post_locations, $post_locations_var );
	}

	return $where;
}

add_filter( 'get_previous_post_where', 'geodir_previous_next_post_where', 10, 5 );
add_filter( 'get_next_post_where', 'geodir_previous_next_post_where', 10, 5 );

/**
 * Returns package information as an objects.
 *
 * @since   2.0.0
 * @package GeoDirectory
 * @deprecated
 *
 * @param object|string $post The post object.
 * @param string $post_type   The post type.
 *
 * @return object Returns filtered package info as an object.
 */
function geodir_get_post_package_id( $post = '', $post_type = '' ) {
	$package = geodir_get_post_package( $post, $post_type );

	$package_id = ! empty( $package ) && ! empty( $package->id ) ? $package->id : 0;

	return $package_id;
}

/**
 * Returns package information as an objects.
 *
 * @since   2.0.0
 * @package GeoDirectory
 * @deprecated
 *
 * @param object|string $post The post object.
 * @param string $post_type   The post type.
 *
 * @return object Returns filtered package info as an object.
 */
function geodir_get_post_package( $post = '', $post_type = '' ) {
	$package = array(
		'id' => 0,
	);

	return (object)apply_filters( 'geodir_get_post_package', (object)$package, $post, $post_type );
}

/**
 * A list of fields that should not be auto replaced.
 *
 * @return array
 */
function geodir_get_no_replace_fields(){
	return array(
		'post_password',
		'submit_ip'
	);
}

/**
 * Replace custom variables in text.
 * 
 * @param $text
 * @param string $post_id
 *
 * @return mixed
 */
function geodir_replace_variables($text,$post_id = ''){
	global $gd_post;

	// only run if we have a GD post and the start of a var
	if(!empty($gd_post->ID) && strpos( $text, '%%' ) !== false){
		$non_replace = geodir_get_no_replace_fields();
		foreach($gd_post as $key => $val){
			if(!in_array($key,$non_replace)){
				$val = apply_filters( 'geodir_replace_variables_' . $key, $val, $text );
				$text = str_replace('%%'.$key.'%%',$val,$text);
			}
		}
	}
	
	return $text;
}

/**
 * Filter post badge match value.
 * 
 * @since 2.0.0.67
 * 
 * @param bool $match_found Ture if match found else False.
 * @param array $args Badge arguments.
 * @param object $gd_post The GD post object.
 * @return bool
 */
function geodir_post_badge_filter_match_found( $match_found, $args, $gd_post ) {
	$match_field = $args['key'];

	if ( $match_field == 'post_category' || $match_field == 'post_tags' ) {
		$search = $args['search'];
		if ( $search !== '' ) {
			$search = array_map( 'trim', explode( ',', stripslashes( $search ) ) );
			$search = array_filter( array_unique( $search ) );
		}

		$value = isset( $gd_post->{$match_field} ) ? $gd_post->{$match_field} : '';
		if ( $value !== '' ) {
			$value = array_map( 'trim', explode( ',', stripslashes( $value ) ) );
			$value = array_filter( array_unique( $value ) );
		}

		if ( $args['condition'] == 'is_contains' ) {
			$match_found = false;

			if ( ! empty( $search ) && ! empty( $value ) ) {
				foreach ( $search as $_search ) {
					if ( in_array( $_search, $value ) ) {
						$match_found = true; // Contains any value
						break;
					}
				}
			}
		} elseif ( $args['condition'] == 'is_not_contains' ) {
			$match_found = false;

			if ( ! empty( $search ) && ! empty( $value ) ) {
				$matches = 0;
				foreach ( $search as $_search ) {
					if ( ! in_array( $_search, $value ) ) {
						$matches++; // Not contains all value
					}
				}

				if ( $matches == count( $search ) ) {
					$match_found = true;
				}
			}
		}
	}

	return $match_found;
}
add_filter( 'geodir_post_badge_check_match_found', 'geodir_post_badge_filter_match_found', 10, 3 );

/**
 * Sanitize text value.
 *
 * @since 2.0.0.67
 *
 * @param string $value Business hours value.
 * @param string $custom_field Custom field.
 * @param int $post_id Post id.
 * @param object $post Post.
 * @param string $update Update.
 * @return string $value Sanitize business hours.
 */
function geodir_validate_custom_field_value_text( $value, $custom_field, $post_id, $post, $update ) {
	if ( $value != '' ) {
		if ( is_array( $value ) ) {
			$value = array_map( 'geodir_clean', $value );
		} else {
			$value = is_scalar( $value ) ? geodir_clean( stripslashes( $value ) ) : $value;
		}
	}
	return $value;
}
add_filter( 'geodir_custom_field_value_checkbox', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_datepicker', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_email', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_multiselect', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_phone', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_radio', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_select', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_text', 'geodir_validate_custom_field_value_text', 10, 5 );
add_filter( 'geodir_custom_field_value_url', 'geodir_validate_custom_field_value_text', 10, 5 );

/**
 * Sanitize textarea/html value.
 *
 * @since 2.0.0.67
 *
 * @param string $value Business hours value.
 * @param string $custom_field Custom field.
 * @param int $post_id Post id.
 * @param object $post Post.
 * @param string $update Update.
 * @return string $value Sanitize business hours.
 */
function geodir_validate_custom_field_value_textarea( $value, $custom_field, $post_id, $post, $update ) {
	if ( $value != '' ) {
		if ( is_array( $value ) ) {
			$value = array_map( 'geodir_sanitize_html_field', $value );
		} else {
			$value = is_scalar( $value ) ? geodir_sanitize_html_field( $value ) : $value;
		}
	}
	return $value;
}
add_filter( 'geodir_custom_field_value_html', 'geodir_validate_custom_field_value_textarea', 10, 5 );
add_filter( 'geodir_custom_field_value_textarea', 'geodir_validate_custom_field_value_textarea', 10, 5 );