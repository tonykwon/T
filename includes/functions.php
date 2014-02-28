<?php

/*
 * pertinent utility function for debugging
 */
if ( !function_exists('pr') ) {

	function pr($o) {
		$output = '<pre>(%s) %s</pre>';
		if ( WP_DEBUG ) {
			$bt = debug_backtrace();
			$callee = reset($bt);
			extract($callee);
			$filename_line = sprintf('%s:%s', basename($file), $line);
			$output = '<pre>'. $filename_line . "\n" . '(%s) %s</pre>';
		}
		echo ( is_array($o) || is_object($o) )
			? sprintf( $output, gettype($o), print_r($o, TRUE) )
			: sprintf( $output, gettype($o), $o );
	}

}

function t_assets() {

	$return = array();

	$types	= array( 'css', 'js' );

	$config = 'assets.json';
	$source = THEME_DIR . DS . $config;

	/* check to see if we have assets.json in our current theme root */
	if ( !file_exists( $source ) ) {
		// load default set of assets from core
		$source = THEME_INCLUDES_DIR . DS . $config;
		if ( !file_exists( $source ) ) {
			return $return;
		}
	}

	$assets = file_get_contents( $source );
	$assets = json_decode( $assets, TRUE );
	if ( !$assets ) {
		pr( __(sprintf('There is an issue with json markup of %s.', $source), GETTEXT_DOMAIN) );
		return;
	}
	$return['source'] = isset($assets['source']) && !empty($assets['source']) ? $assets['source'] : '';

	foreach( $types as $type ) {

		switch( $type ) {
			case 'css':
				if ( isset($assets[$type]) ) {
					$return[$type] = t_assets_css( $assets[$type] );
				}
				break;
			case 'js':
				if ( isset($assets[$type]) ) {
					$return[$type] = t_assets_js( $assets[$type] );
				}
				break;
		}

	}

	return apply_filters('t_hook_assets', $return);

}

function t_assets_css( $items = array() ) {
	
	$return = array();

	if ( !$items ) {
		return $return;
	}

	$type = 'css';

	// read configuration file
	$ver  = isset(T::$configs['assets']['foundation']) ? T::$configs['assets']['foundation'] : '5.1.1';
	$base = isset(T::$configs['assets']['base']) ? T::$configs['assets']['base'] : 'assets';

	foreach( $items as $ver => $assets ) {
		foreach($assets as $handle => $asset) {
			if ( isset($asset['sub']) && !empty($asset['sub']) ) {
				$_sub = strtr($asset['sub'], array('/' => DS));
				$path = sprintf('%s%s%s%s%s%s%s%s%s%s%s', THEME_DIR, DS, $base, DS, $type, DS, $ver, DS, $_sub, DS, $asset['name']);
				$src  = sprintf('%s/%s/%s/%s/%s/%s', THEME_URI, $base, $type, $ver, $_sub, $asset['name']);
			} else {
				$path = sprintf('%s%s%s%s%s%s%s%s%s', THEME_DIR, DS, $base, DS, $type, DS, $ver, DS, $asset['name']);
				$src  = sprintf('%s/%s/%s/%s/%s', THEME_URI, $base, $type, $ver, $asset['name']);
			}
			$return[$handle] = array(
				'path' => $path,
				'src' => $src,
				'deps' => $asset['deps'],
				'media' => $asset['media']
			);
		}
	}

	return apply_filters('t_hook_assets_css', $return);

}

function t_assets_js( $items = array() ) {

	$return = array();

	if ( !$items ) {
		return $return;
	}

	$type = 'js';

	// read from configuration file
	$ver  = isset(T::$configs['assets']['foundation']) ? T::$configs['assets']['foundation'] : '5.1.1';
	$base = isset(T::$configs['assets']['base']) ? T::$configs['assets']['base'] : 'assets';

	foreach( $items as $ver => $assets ) {
		foreach($assets as $handle => $asset) {
			if ( isset($asset['sub']) && !empty($asset['sub']) ) {
				$_sub = strtr($asset['sub'], array('/' => DS));
				$path = sprintf('%s%s%s%s%s%s%s%s%s%s%s', THEME_DIR, DS, $base, DS, $type, DS, $ver, DS, $_sub, DS, $asset['name']);
				$src  = sprintf('%s/%s/%s/%s/%s/%s', THEME_URI, $base, $type, $ver, $_sub, $asset['name']);
			} else {
				$path = sprintf('%s%s%s%s%s%s%s%s%s', THEME_DIR, DS, $base, DS, $type, DS, $ver, DS, $asset['name']);
				$src  = sprintf('%s/%s/%s/%s/%s', THEME_URI, $base, $type, $ver, $asset['name']);
			}
			$return[$handle] = array(
				'path' => $path,
				'src' => $src,
				'deps' => $asset['deps'],
				'in_footer' => $asset['in_footer']
			);
		}
	}

	return apply_filters('t_hook_assets_js', $return);

}

/**
 * check to see if we need to put in a paginator
 *
 * @access public
 * @param none
 * @return boolean
 */
function t_needs_pagination() {

	global $wp_query;

	if (
		is_archive() OR is_home() OR
		( $wp_query->is_posts_page ) OR
		( $wp_query->is_singular && !is_page() && !is_single() ) OR
		( $wp_query->is_singular && $wp_query->is_attachment ) OR
		is_search() ) {
			return true;
	}

	return false;

}

function t_get_post_type( $echo = false ) {

	global $wp_query;

	if ( isset($wp_query->queried_object) && $wp_query->queried_object ) {
		if ( array_key_exists( 'taxonomy', $wp_query->queried_object ) ) {
			$tax_object = get_taxonomy( $wp_query->queried_object->taxonomy );
			return $tax_object->labels->singular_name;
		}
	}

	$post_type = get_query_var('post_type');

	if ( $post_type == 'any' ) {
		// e.g. we are searching
		return $post_type;
	}

	if ( empty($post_type) ) {
		$post		= reset($wp_query->posts);
		$post_type	= $post->post_type;
	}
	$post_type_obj	= get_post_type_object($post_type);

	return t_needs_pagination()
		? apply_filters('t_hook_post_type', $post_type_obj->labels->name)
		: apply_filters('t_hook_post_type', $post_type_obj->labels->singular_name);

}

function t_get_archive_type() {

	$archives = array();

	if ( is_day() ) {
		$archives = array(
			'type' => __( 'Daily', GETTEXT_DOMAIN ),
			'format' => get_the_date()
		);
	} else if ( is_month() ) {
		$archives = array(
			'type' => __( 'Monthly', GETTEXT_DOMAIN ),
			'format' => get_the_date(
				_x( 'F Y', 'monthly archives date format', GETTEXT_DOMAIN )
			)
		);
	} else if ( is_year() ) {
		$archives = array(
			'type' => __( 'Yearly', GETTEXT_DOMAIN ),
			'format' => get_the_date(
				_x( 'Y', 'yearly archives date format', GETTEXT_DOMAIN )
			)
		);
	} else {
		$archives = array(
			'type' => __( 'Archives', GETTEXT_DOMAIN ),
			'format' => ''
		);
	}

	return apply_filters('t_hook_archive_type', $archives);

}

/**
 * returns useful information about post terms
 *
 * @return array
 * @internal should be called from singular post templates
 */
function t_get_post_term_info() {

	global $wp_query, $post;

	if ( !is_singular() ) {
		return array();
	}

	$post_type		= $wp_query->queried_object->post_type;
	$post_type_obj	= get_post_type_object($post_type);
	$tag_list		= $category_list = '';

	/* not all custom post types created has $post_type_obj->taxonomies set */
	if ( !$post_type_obj->taxonomies ) {
		$_taxonomies = get_taxonomies(array('object_type' => array( $post_type )));
		if ( $_taxonomies ) {
			$post_type_obj->taxonomies = array_values( $_taxonomies );
		}
		unset( $_taxonomies );
	}

	if ( $post_type_obj->taxonomies ) {

		/* likely processing custom post type */
		$taxonomies = array();
		
		foreach( $post_type_obj->taxonomies as $taxonomy ) {

			/* attempt to detect if we are processing post `category` and/or post `tag` */
			$exploded	= explode("_", $taxonomy);
			$_type		= strtr( $taxonomy, array( $post_type.'_' => '') );

			$taxonomies[ "term_{$_type}" ] = $taxonomy;
			$taxonomies[ "{$_type}_list" ] = get_the_term_list( $post->ID, $taxonomy, ' ', ', ', '' );

			if ( in_array('categories', $exploded) || in_array('category', $exploded) ) {
				
				$category_list = $taxonomies[ "{$_type}_list" ];

			} else if ( in_array('tags', $exploded) || in_array('tag', $exploded) ) {

				$tag_list = $taxonomies[ "{$_type}_list" ];
				
			}

		}

		extract($taxonomies);

	} else {

		/* no taxonomies specified */

		/*
		 * TODO: perhaps this isn't necessary at all
		 */

		/* aka Post Category */
		$term_category = strtr( $post_type, array('post' => 'category') );
		$category_list = get_the_term_list( $post->ID, $term_category, ' ', ', ', '' );
		if ( is_wp_error($category_list) ) {
			// e.g. invalid taxonomy
			$term_category = '';
			$category_list = '';
		}

		/* aka Post Tag */
		$term_tag = strtr( $post_type, array('post' => 'post_tag') );
		$tag_list = get_the_term_list( $post->ID, $term_tag, ' ', ', ', '' );
		if ( is_wp_error($tag_list) ) {
			// e.g. invalid taxonomy
			$term_tag = '';
			$tag_list = '';
		}

	}

	if ( '' != $tag_list ) {
		$utility_text = __(
			'This entry was posted in %1$s and tagged %2$s by <a href="%6$s">%5$s</a>. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', GETTEXT_DOMAIN
		);
	} elseif ( '' != $category_list ) {
		$utility_text = __(
			'This entry was posted in %1$s by <a href="%6$s">%5$s</a>. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', GETTEXT_DOMAIN
		);
	} else {
		$utility_text = __(
			'This entry was posted by <a href="%6$s">%5$s</a>. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', GETTEXT_DOMAIN
		);
	}

	return apply_filters('t_hook_post_term_info', compact('post_type', 'term_category', 'category_list', 'term_tag', 'tag_list', 'utility_text'));

}


/* see hooks */

/**
 * gets the post author's url
 * 
 * @return string url
 * @see core/hooks.php
 * @internal use t_author_url hook if you need to override 
 */
function t_get_author_url() {

	return apply_filters('t_hook_author_url', sprintf('<a href="%s" rel="author" class="fn url">%s</a>', esc_url(get_author_posts_url(get_the_author_meta('ID'))), get_the_author_meta('display_name')));

}

/**
 * gets the posted by information
 * 
 * @return string url
 * @see core/hooks.php
 * @internal use t_author_posted_by hook if you need to override 
 */
function t_get_author_posted_by() {

	$post_id = get_the_id();
	$author_url	= t_get_author_url();
	
	return apply_filters('t_author_posted_by', sprintf(__('Posted by %s on %s'), $author_url, sprintf('<a href="%s" title="%s">%s</a>', esc_url(get_permalink($post_id)), esc_attr(get_the_title($post_id)), get_the_time('F jS, Y'))));

}


/**
 * returns current url using home_url function
 *
 *
 * @access public
 * @return string @url
 * @see http://kovshenin.com/2012/current-url-in-wordpress/
 */
function t_current_url( $trailingslashit = true ) {

	global $wp;

	$url = add_query_arg('', '', home_url($wp->request));
	if ( $trailingslashit ) {
		$url = trailingslashit($url);
	}

	return $url;

}