<?php

/**
 * load this to reduce generated html size
 */

add_action( 'template_redirect', 'td_clean_header' );

function td_clean_header() {

	/* Display the links to the extra feeds such as category feeds */
	remove_action( 'wp_head', 'feed_links_extra', 3 );

	/* Display the links to the general feeds: Post and Comment Feed */
	remove_action( 'wp_head', 'feed_links', 2 );

	/* Display the link to the Really Simple Discovery service endpoint, EditURI link */
	remove_action( 'wp_head', 'rsd_link' );

	/* Display the link to the Windows Live Writer manifest file. */
	remove_action( 'wp_head', 'wlwmanifest_link' );

	/* index link */
	remove_action( 'wp_head', 'index_rel_link' );

	/* prev link */
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );

	/* start link */
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );

	/* Display relational links for the posts adjacent to the current post. */
	remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );

	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

	/* WordPress version */
	remove_action( 'wp_head', 'wp_generator' );

}