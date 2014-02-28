<?php

if ( !defined('DS') ) {
	define( 'DS', DIRECTORY_SEPARATOR );
}

if ( !defined('THEME_DIR') ) {
	/* retrieve template directory Path for the current theme. */
	define( 'THEME_DIR', get_template_directory() );
}

if ( !defined('THEME_INCLUDES_DIR') ) {
	/* retrieve main theme includes directory Path for the current theme. */
	define( 'THEME_INCLUDES_DIR', dirname(__FILE__) );
}

if ( !defined('THEME_URI') ) {
	/* retrieve template directory URI for the current theme. Checks for SSL. */
	define( 'THEME_URI', get_template_directory_uri() ); 
}

if ( !defined('THEME_BASE') ) {
	/* base directory name of parent theme */
	define( 'THEME_BASE', basename(dirname(THEME_DIR . DS . 'functions.php')) );
}

if ( !defined('CHILD_THEME_DIR') ) {
	/* retrieve stylesheet directory Path for the current theme/child theme. */
	define( 'CHILD_THEME_DIR', get_stylesheet_directory() );
} 

if ( !defined('CHILD_THEME_URI') ) {
	/* retrieve stylesheet directory URI for the current theme/child theme. Checks for SSL. */
	define( 'CHILD_THEME_URI', get_stylesheet_directory_uri() );
}

if ( !defined('CHILD_THEME_BASE') ) {
	/* base directory name of child theme */
	define( 'CHILD_THEME_BASE',	basename(dirname(CHILD_THEME_DIR . DS . 'functions.php')) );
}

if ( !defined('HOME_URL') ) {
	/* e.g. http://www.example.com/ */
	define( 'HOME_URL', home_url('/') ); 
}

if ( !defined('HOME_URL_NO_TS') ) {
	/* e.g. http://www.example.com */
	define( 'HOME_URL_NO_TS', home_url() );
}

if ( !defined('SITE_URL') ) {
	/* e.g. http://www.example.com OR http://www.example.com/wordpress */
	define( 'SITE_URL', site_url() );
}

if ( !defined('ADMIN_URL') ) {
	/* e.g. http://www.example.com/wp-admin */
	define( 'ADMIN_URL', admin_url() );
}

if ( !defined('INCLUDES_URL') ) {
	/* e.g. http://www.example.com/wp-includes */
	define( 'INCLUDES_URL', includes_url() );
}

if ( !defined('CONTENT_URL')) {
	/* e.g. http://www.example.com/wp-content */
	define( 'CONTENT_URL', content_url() );
}

if ( !defined('PLUGINS_URL')) {
	/* e.g. http://www.example.com/wp-content/plugins */
	define( 'PLUGINS_URL', plugins_url() );
}

if ( !defined('TEMPLATE_NAME')) {
	/* e.g. _templates/default */
	define( 'TEMPLATE_NAME', 'default' );
}

if ( !defined('TEMPLATES_DIR')) {
	/* our pre-built templates */
	define( 'TEMPLATES_DIR', THEME_DIR . DS . '_templates' . DS . TEMPLATE_NAME . DS );
}

if ( !defined('GETTEXT_DOMAIN')) {
	/* e.g. topdraw */
	define( 'GETTEXT_DOMAIN', THEME_BASE );
}