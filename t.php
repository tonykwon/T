<?php

/**
 * T t.php
 * A core class that does lots for you
 * 
 * @package WordPress
 * @subpackage T
 * @since T 0.1
 */

/* Singleton Class */

class T {

	/* singleton instance */
	protected static $instance = NULL;

	/* configuration array */
	public static $configs = array();

	/* debug array for debugging */
	protected static $debug = array();

	/* useful when passing arguments to template parts */
	protected static $template_vars = array();

	/**
	 * our static class constructor
	 * 
	 * @access protected
	 * @return void
	 */
	protected function __construct() {

		self::_includes();
		self::_bootstrap();
		self::_gather_assets();

		add_action( 'after_setup_theme', array( __CLASS__, 'after_setup_theme' ) );

	}

	/**
	 * magic method __clone()
	 * 
	 * @access private
	 * @return void
	 */
	private function __clone() {

		throw new Exception( __('You can not duplicate a Singleton class.') );

	}

	/**
	 * static class instantiator
	 * 
	 * @access public
	 * @return object static class instance
	 */
	public static function get_instance() {

		return ( self::$instance == NULL ) ? new self() : self::$instance;

	}

	/**
	 * simply includes necessary files prior to performing bootstrap calls
	 *
	 * @access private
	 * @return void
	 */
	private static function _includes() {
		
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'constants.php';
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'hooks.php';
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'classes.php';

		// load if optimize is set to true
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'hooks-optimization.php';

	}

	/**
	 * read json configuration file and setup the config array
	 *
	 * @access private
	 * @return void
	 */
	private static function _bootstrap() {

		$config_files = array( 'config.json', 'menus.json', 'sidebars.json' );

		foreach( $config_files as $config ) {

			/* look for these configuration files from the theme root directory */
			$source = THEME_DIR . DS . $config;
			$merge  = false;

			if ( file_exists($source) ) {

				$configs = file_get_contents($source);
				$configs = json_decode($configs, TRUE);
				
				if ( !$configs ) {
					self::$debug[__FUNCTION__.':('.__LINE__.')'] = __(
						"Invalid JSON File, check %s for its markup.",
						$source
					);
				} else {
					$merge = true;
				}

			} else {

				$source  = THEME_INCLUDES_DIR . DS . $config;
				$configs = file_get_contents($source);
				$configs = json_decode($configs, TRUE);
				
				if ( !$configs ) {
					self::$debug[__FUNCTION__.':('.__LINE__.')'] = __(
						"Invalid JSON File, check %s for its markup.",
						$source
					);
				} else {
					$merge = true;
				}

			}

			if ( $merge ) {
				self::$configs = array_merge( self::$configs, $configs );
			}

		}

		self::$configs = array_merge( self::$configs, $configs );

		/* does the server support output_buffering? Ensure this is turned on */
		self::$configs['can_flush_early'] = ( ini_get( 'output_buffering' ) > 0 ) ? TRUE : FALSE;

	}

	/**
	 * based on gathered js assets, register then enqueue resources
	 *
	 * @access private
	 * @return void
	 */
	private static function _gather_assets() {

		$gathered_assets = t_assets(); // see core/includes/functions.php

		if ( isset($gathered_assets['css']) ) {
			self::$configs['assets']['css']['enqueue'] = array_merge(
				self::$configs['assets']['css']['enqueue'],
				$gathered_assets['css']
			);
		}

		if ( isset($gathered_assets['js']) ) {
			self::$configs['assets']['js']['enqueue'] = array_merge(
				self::$configs['assets']['js']['enqueue'],
				$gathered_assets['js']
			);
		}
		// pr( self::$configs['assets'] ); exit;
		
	}

	/**
	 * main routine
	 *
	 * @access public
	 * @return void
	 */
	public static function after_setup_theme() {

		global $pagenow;

		/* editor-style.css to match the theme style */
		add_editor_style();

		/* determins the admin bar behaviour */
		show_admin_bar( is_user_logged_in() );

		/* add theme support for custom menus */
		add_theme_support( 'nav-menus' );

		/* add theme support for automatic feed links. */
		add_theme_support( 'automatic-feed-links' );

		/* add theme support for post thumbnails (featured images). */
		add_theme_support( 'post-thumbnails' );

		/**
		 * css assets
		 */
		/* enqueue css assets */
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets_css' ) );

		/* head elements that need to be shielded from asset relocation (if applied) */
		add_action( 'wp_head', array( __CLASS__, 'before_wp_head' ), 1 );

		/* head elements that need to be shielded from asset relocation (if applied) */
		add_action( 'wp_head', array( __CLASS__, 'after_wp_head' ), 98 );

		/* simply injects a placeholder text so that we can relocate all css styles as needed */
		add_action( 'wp_head', array( __CLASS__, 'assets_css_way_point' ), 99  );

		/* we want to load our theme css as the `last` css to be loaded */
		add_action( 'wp_head', array( __CLASS__, 'assets_css_styles' ), 100 );

		/**
		 * js assets
		 */
		/* enqueue js assets */
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets_js'  ) );

		/* footer elements that need to be secured from asset relocation */
		add_action( 'wp_footer', array( __CLASS__, 'before_wp_footer' ), 1 );

		/* head elements that need to be secured from asset relocation */
		add_action( 'wp_footer', array( __CLASS__, 'after_wp_footer' ), 98 );

		/* simply injects a placeholder text so that we can relocate all js scripts as needed */
		add_action( 'wp_footer', array( __CLASS__, 'assets_js_way_point' ), 99 );

		/* we inspect the content to see if we need to extract any inline styles/scripts */
		add_filter( 'the_content', array( __CLASS__, 'extract_inline_scripts' ), 11, 1 ); // do_shortcode is done @ priority 11

		/* let's determine which template we should include */
		if ( self::$configs['templates'] ) {
			foreach( self::$configs['templates'] as $template ) {
				add_filter( $template, array( __CLASS__, 'template_filter' ), 12, 1 );
			}
		}

		/* register menus if any */
		add_action( 'init', array( __CLASS__, 'register_menus' ) );

		/* register widgeted areas(sidebars) to be used */
		add_action( 'widgets_init', array( __CLASS__, 'register_widgeted_areas' ) );

		if ( !is_admin() && ($pagenow != 'wp-login.php') ) {
			/* moving scripts to the footer */
			remove_action( 'wp_head', 'wp_print_scripts' ); 
			remove_action( 'wp_head', 'wp_print_head_scripts', 9 );

			add_action( 'wp_footer', 'wp_print_head_scripts', 1 );
			add_action( 'wp_footer', 'wp_print_scripts', 1 );

			add_action( 'init', array( __CLASS__, 'buffer_start' ), 1 );
			add_action( 'shutdown', array( __CLASS__, 'buffer_end' ), 100 );
		}

		/* capture the content of dynamic_sidebar call: this allows us to extract scripts injected within the sidebar */
		add_action( '_hook_before_dynamic_sidebar', array( __CLASS__, 'before_dynamic_sidebar') );
		add_action( '_hook_after_dynamic_sidebar', array( __CLASS__, 'after_dynamic_sidebar') );

		/* let's fix up the author archives permalink for our built in post type `post` */
		add_filter( 'author_link', array( __CLASS__, 'author_link' ), 10, 3 );

	}

	/**
	 * before the dynamic_sidebar call
	 *
	 * @internal add_action( '_hook_before_dynamic_sidebar', array( __CLASS__, 'before_dynamic_sidebar') );
	 * @return void
	 */
	public static function before_dynamic_sidebar() {

		ob_start();

	}

	/**
	 * after the dynamic sidebar call
	 *
	 * @internal add_action( '_hook_after_dynamic_sidebar', array( __CLASS__, 'after_dynamic_sidebar') );
	 * @return void
	 */
	public static function after_dynamic_sidebar() {

		/* capture the dynamic_sidebar content then do some filtering */
		$content = ob_get_contents(); ob_end_clean();
		$content = self::extract_inline_scripts($content);
		$content = str_replace(array("\r\n", "\r"), '', $content); // remove tabs & newlines
		// echo '<textarea>', $content, '</textarea>';
		//
		echo $content;

	}

	/**
	 * try to extract inline scripts and collect them to
	 * 
	 * 	assets/js/inlines  array
	 * 		or
	 * 	assets/css/inlines array
	 * 	
	 * so that we can move them to appropriate area
	 *
	 * NOTE: inline js snipepts can easily be moved in the designated area when
	 * assets_js_way_point hook is running, however, inline css snippets will need
	 * to rely on output_buffering
	 * 
	 * @access public
	 * @return string $content
	 */
	public static function extract_inline_scripts( $content = '' ) {

		$types = array(
			'css' => array(
				'@<style[.\w\d\s\S]*</style>@',
				'/<link.*stylesheet[^>]*(.*)\/>/Uis'
			),
			'js'  => array(
				'/<script.*javascript[^>]*>(.*)<\/script>/Uis'
			)
		);

		foreach( $types as $type => $patterns ) {

			$assets = self::$configs['assets'][$type];
			$to_relocate = array();

			foreach( $patterns as $the_pattern ) {
				
				preg_match_all( $the_pattern, $content, $matches );

				if ( $matches ) {
					$_matches = reset($matches); // first one has all matched items
					foreach( $_matches as $_match ) {
						$to_relocate[] = trim($_match);
						$content = strtr( $content, array($_match => '') );
					}
					$content = strtr( $content, array($assets['config']['relocate'] => '') );
					$content = trim($content);
				}

				if ( $to_relocate ) {
					self::$configs['assets'][$type]['inlines'] = array_merge(
						self::$configs['assets'][$type]['inlines'],
						$to_relocate
					);
				}

			}

		}
		
		return $content;

	}

	/**
	 * based on gathered css assets, register then enqueue resources
	 *
	 * @access public
	 * @return void
	 */
	public static function assets_css() {

		global $wp_styles, $wp_version;

		$assets = self::$configs['assets']['css']; extract($assets); // config, inlines, enqueue

	    foreach( $enqueue as $handle => $params ) {
	    	if ( file_exists( $params['path'] ) ) {
	            $version = (isset($config['timestamp']) && $config['timestamp']) ? filemtime( $params['path'] ) : $wp_version;
	    		wp_register_style( $handle, $params['src'], $params['deps'], $version, !$params['media'] ? 'all' : $params['media'] );
				wp_enqueue_style( $handle );
				/* used when loading IE conditional stylesheets */
				if ( array_key_exists('extra', $params) && $params['extra'] ) {
					foreach( $params['extra'] as $k => $v ) {
						$wp_styles->add_data( $handle, $k, $v );
					}
				}
			}
	    }

	}

	/**
	 * registers and enqueues WordPress theme master style sheet
	 *
	 * @access public
	 * @param null
	 * @return void
	 * @see $assets
	 * @internal add_action( 'wp_head', array( __CLASS__, 'assets_css_styles' ) );
	 */
	public static function assets_css_styles() {

		$handle	= 'master';
		$path	= THEME_DIR . DIRECTORY_SEPARATOR . 'style.css';
		$assets = self::$configs['assets']['css'];

		if ( file_exists($path) ) {
			$version	= $assets['config']['timestamp'] ? filemtime( $path ) : $wp_version;
			$src		= THEME_URI . '/' . 'style.css';
			wp_register_style( $handle, $src, FALSE, $version );
			wp_enqueue_style( $handle );
			wp_print_styles( $handle );
		}

	}

	/**
	 * injects html comments for css assets to be relocated
	 *
	 * @access public
	 * @return void
	 */
	public static function assets_css_way_point() {
		
		$type = 'css';

		echo self::$configs['assets'][$type]['config']['relocate']; // e.g. '<!-- CSS ASSETS TO RELOCATE -->';

		/*
		 * NOTE: unlike
		 * 			self::$configs['assets']['js']['inlines'],
		 *			self::$configs['assets']['css']['inlines'] won't get populated
		 *			until
		 *			hooks that run on `the_content` AND sidebar callbacks are completed.
		 * therefore,
		 * 			if output_buffering is supported
		 * 				we relocate inlines using tidy_html hook
		 * 			else
		 * 				we relocate inlines using assets_js_way_point
		 * 				(so they end up in the footer, which isn't the best thing)
		 * 				
		 * the best thing would be to configure the host to support output_buffering
		 *
		 */

	}

	/**
	 * based on gathered js assets, register then enqueue resources
	 *
	 * @access public
	 * @return void
	 */
	public static function assets_js() {

		global $wp_scripts, $wp_version;

		$assets = self::$configs['assets']['js']; extract($assets); // config, inlines, enqueue

		foreach( $enqueue as $handle => $params ) {
			//pr( $params );
			if ( file_exists($params['path']) ) {
				$version = (isset($config['timestamp']) && $config['timestamp']) ? filemtime( $params['path'] ) : $wp_version;
				wp_register_script( $handle, $params['src'], $params['deps'], $version, $params['in_footer'] );
				wp_enqueue_script( $handle );
				if ( array_key_exists('extra', $params) && $params['extra'] ) {
					/* used when loading IE conditional javascripts */
					foreach( $params['extra'] as $k => $v ) {
						/* not fully supported yet */
						$wp_scripts->add_data( $handle, $k, $v );
					}
				}
			}
		}

	}

	/**
	 * injects html comments for js assets to be relocated
	 *
	 * @access public
	 * @return void
	 */

	public static function assets_js_way_point() {
		
		$type = 'js';
		
		echo self::$configs['assets'][$type]['config']['relocate']; // e.g. '<!-- JS ASSETS TO RELOCATE -->';

		/* if we have any inline scripts that we've detected */
		if ( isset(self::$configs['assets'][$type]['inlines']) && self::$configs['assets'][$type]['inlines'] ) {
			foreach( self::$configs['assets'][$type]['inlines'] as $inline_script ) {
				echo $inline_script, "\n";
			}
		}

	}

	/**
	 * we hook to {$type}_template filter to see if we can load our
	 * `base template` instead of the WordPress deteremined template
	 *
	 * @access public
	 * @param string $default_template (which we will ignore for comparison)
	 * @return string
	 * @see /wp-includes/template-loader.php
	 * @internal apply_filters( "{$type}_template", locate_template( $templates ) )
	 */
	public static function template_filter( $default_template ) {

		global $wp_query, $post;

		$current_filter	= current_filter(); // e.g. 404_template || frontpage_template

		list( $type, $template ) = explode("_", $current_filter);

		$type = ( $type == 'frontpage' ) ? 'front-page' : $type;
		$type = preg_replace( '|[^a-z0-9-]+|', '', $type );

		$templates = array();

		/* some template filters require extra attention */
		switch( $type ) {

			case 'front-page':
				$templates = self::_template_filter_front_page( $type );
				break;

			case 'page':
				$templates = self::_template_filter_page( $type, $default_template );
				break;

			case 'tag':
			case 'category':
				$templates = self::_template_filter_tag_or_category( $type );
				break;

			case 'author':
				$templates = self::_template_filter_author( $type );
				break;

			case 'image':
				$templates = self::_template_filter_image( $type );
				break;

			case 'attachment':
				$templates = self::_template_filter_attachment( $type );
				break;

			case 'single':
				$templates = self::_template_filter_single( $type );
				break;

			case 'taxonomy':
				$templates = self::_template_filter_taxonomy( $type );
				break;

			case 'archive':
				$templates = self::_template_filter_archive( $type );
				break;

			default:
				$templates[] = "{$type}.php";
		}

		/* do we have our base template in the templates directory? */
		$located = self::locate_template( $templates );

		self::$debug[__FUNCTION__.':('.__LINE__.'):'.$current_filter] = $templates;

		self::$debug[__FUNCTION__.':('.__LINE__.')'] = array(
			'current_filter' => $current_filter,
			'type' => $type,
			'template' => $current_filter,
			'default_template' => $default_template,
			'templates' => $templates,
			'located' => $located
		);

		return ($located == '') ? $default_template : $located;

	}

	/**
	 * determine the order of template lookups for `frontpage_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_front_page( $type ) {

		$templates = array();

		$show_on_front = get_option('show_on_front');
		switch( $show_on_front ) {
			case 'posts':
				$templates[] = 'home.php';
				break;
			case 'page':
				$templates[] = sprintf("%s.php", $type); // front-page.php
				break;
		}

		return $templates;

	}

	/**
	 * determine the order of template lookups for `page_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @param  string $default_template WordPress specified default template to be loaded
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_page( $type, $default_template ) {

		global $post;

		$templates = array();

		if ( $post ) {
			$templates[] = sprintf("%s-%s.php", $type, $post->post_name);
			$templates[] = sprintf("%s-%d.php", $type, $post->ID);
			$templates[] = sprintf("%s.php", $type);
			if ( !empty($default_template) ) {
				// e.g. custom template has been specified
				array_unshift($templates, basename($default_template));	
			}
		}

		return $templates;
	}

	/**
	 * determine the order of template lookups for `tag_template` or `category_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_tag_or_category( $type ) {

		global $wp_query;

		$templates = array();

		if ( $wp_query->queried_object && ($wp_query->queried_object_id > 0) ) {
			$taxonomy = $wp_query->queried_object;
			$templates[] = sprintf("%s-%s.php", $type, $taxonomy->slug);
			$templates[] = sprintf("%s-%d.php", $type, $taxonomy->term_id);
		}
		$templates[] = sprintf("%s.php", $type);

		return $templates;

	}

	/**
	 * determine the order of template lookups for `author_template`
	 *
	 * @access private
	 * 
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_author( $type ) {

		global $post;

		$templates = array();
		if ( $post ) {
			$templates[] = sprintf("%s-%s.php", $type, get_the_author_meta('user_nicename', $post->post_author));
			$templates[] = sprintf("%s-%d.php", $type, $post->post_author);
			$templates[] = sprintf("%s.php", $type);
		}

		return $templates;

	}

	/**
	 * determine the order of template lookups for `image_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_image( $type ) {

		global $post;

		$templates  = array();
		$templates[]= sprintf('%s.php', $type);

		return $templates;

	}

	/**
	 * determine the order of template lookups for `attachment_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_attachment( $type ) {

		global $post;

		$templates = array();

		$allowed		= get_allowed_mime_types();
		$post_mime_type	= get_post_mime_type($post->ID);

		// check if this is allowed mime type
		if ( !empty($post_mime_type) ) {
			// we have a mime type set
			list($mimetype, $mimesubtype) = explode("/", $post_mime_type);
			$templates[] = sprintf('%s.php', $mimetype);
			$templates[] = sprintf('%s.php', $mimesubtype);
			$templates[] = sprintf('%s_%s.php', $mimetype, $mimesubtype);
		}
		$templates[] = sprintf('%s.php', $type);

		return $templates;

	}

	/**
	 * determine the order of template lookups for `single_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_single( $type ) {

		global $post;

		$templates = array();

		switch ( $post->post_type ) {
			case 'attachment':
				// handled by attachment_template
				break;
			default:
				// e.g. post or custom post
				$templates[] = sprintf('%s-%s.php', $type, $post->post_type);
		}

		$templates[] = 'single.php';

		return $templates;

	}

	/**
	 * determine the order of template lookups for `taxonomy_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 *
	 * @todo do we need to ensure $taxonomy and $tax2 match?
	 */
	private static function _template_filter_taxonomy( $type ) {

		global $wp_query;

		$templates = array();

		$tax_query = $wp_query->tax_query;

		if ( $wp_query->queried_object_id ) {
			/* doing taxonomy query and found posts */
			$tax_query	= $wp_query->tax_query;
			$taxonomy	= get_query_var('taxonomy');
			$term		= get_query_var('term');

			/* do we need to ensure taxonomy and tax2 match? */
			$query		= reset($tax_query->queries);
			$tax2		= $query['taxonomy'];
			$t2			= reset($query['terms']);

			if ( !empty($taxonomy) && !empty($term) ) {
				$templates[] = sprintf('%s-%s-%s.php', $type, $taxonomy, $term);
				$templates[] = sprintf('%s-%s.php', $type, $taxonomy);
			}
		}

		$templates[] = sprintf('%s.php', $type);

		return $templates;

	}

	/**
	 * determine the order of template lookups for `archive_template`
	 *
	 * @access private
	 * @param  string $type based on current_filter()
	 * @return array  $templates list of template files to do the look up on
	 */
	private static function _template_filter_archive( $type ) {

		global $wp_query;

		$templates = array();

		if ( is_post_type_archive() ) {
			$post_type = get_query_var('post_type');
		}

		if ( !empty($post_type) ) {
			$templates[] = sprintf('%s-%s.php', $type, $post_type);
		}
		$templates[] = sprintf('%s.php', $type);

		return $templates;


	}

	/**
	 * this function is an extension to the function `locate_template` in /wp-includes/template.php
	 * we simply add a check so that we can load templates from
	 * - base template directory	/wp-content/themes/t/_templates/
	 *
	 * @access public
	 * @param array $template_names, boolean $load, boolean $require_once
	 * @see /wp-includes/template.php
	 * @return false|string
	 */
	public static function locate_template( $template_names, $load = false, $require_once = true ) {

		$located = '';

		$names = (array)$template_names;

		foreach ( $names as $template_name ) {

			if ( !$template_name )
				continue;

			if ( file_exists(STYLESHEETPATH . '/' . $template_name) ) {
				$located = STYLESHEETPATH . '/' . $template_name;
				break;
			} else if ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {
				$located = TEMPLATEPATH . '/' . $template_name;
				break;
			} else if ( file_exists(TEMPLATES_DIR . $template_name) ) {
				/* additional check to look under base template directory */
				$located = TEMPLATES_DIR . $template_name;
				break;
			}

		}

		if ( $load && '' != $located ) {
			load_template( $located, $require_once );
		}

		return $located;

	}

	/**
	 * simple wrapper function for get_sidebar that checks for the
	 * sidebar under our base template directory
	 *
	 * @access public
	 * @param string $name
	 * @return void
	 */
	public static function get_sidebar( $name = '' ) {

        $templates = array();

        if ( isset($name) ) {
        	$templates[] = "sidebar-{$name}.php";
        }
        $templates[] = 'sidebar.php';

        $located = self::locate_template($templates, TRUE, TRUE);

        if ( $located == '' ) {
        	get_sidebar( $name );
        }

	}

	/**
	 * simply registers nav menus based on $configs['menus'] value
	 *
	 * @access public
	 * @return void
	 */
	public static function register_menus() {

		if ( isset(self::$configs['menus']) && self::$configs['menus'] ) {
			register_nav_menus(self::$configs['menus']);
		}

	}

	/**
	 * simply registers sidebars based on $configs['sidebars']
	 *
	 * @access public
	 * @return void
	 */
	public static function register_widgeted_areas() {

		if ( isset(self::$configs['sidebars']) && self::$configs['sidebars'] ) {
			foreach( self::$configs['sidebars'] as $sidebar ) {
				register_sidebar( $sidebar );
			}
		}

	}

	/**
	 * simply returns the content of a hook file (if exists)
	 *
	 * @access public
	 * @param string $hook_file_to_load
	 */
	private static function _load_hook_file( $hook_file_to_load ) {

		/* if we have a file named same as this function, let's load'em up: e.g. _hooks/after_wp_head.php */
		$content = '';
		if ( file_exists($hook_file_to_load) ) {
			if ( is_file($hook_file_to_load) ) {
				ob_start();
				include_once $hook_file_to_load;
				$content = ob_get_contents();
				ob_end_clean();
			}
		}

		return $content;

	}

	/**
	 * prints before_x, after_x or before_y, after_y hook files
	 * 
	 * @param  string $function_name callback name
	 * @return void
	 */
	private static function _hook_file( $function_name = '' ) {

		$placeholder	= "\n<!-- [*] " . $function_name . " -->\n";	// before wp_head()
		$file			= THEME_DIR . '/_hf/' . $function_name . '.php';
		$file_content	= self::_load_hook_file( $file );
		
		echo "{$placeholder}{$file_content}\n";

	}

	/**
	 * inserts a place holder HTML comment on `before_wp_head` hook then injects the hook file content
	 *
	 * @access public
	 * @return void
	 * @internal look for _hooks/before_wp_head.php
	 */
	public static function before_wp_head() {

		self::_hook_file(__FUNCTION__);

	}

	/**
	 * inserts a place holder HTML comment on `after_wp_head` hook then injects the hook file content
	 *
	 * @access public
	 * @return void
	 * @internal look for _hooks/after_wp_head.php
	 */
	public static function after_wp_head() {

		self::_hook_file(__FUNCTION__);

	}

	/**
	 * inserts a place holder HTML comment on `before_wp_footer` hook then injects the hook file content
	 *
	 * @access public
	 * @return void
	 * @internal look for _hooks/before_wp_footer.php
	 */
	public static function before_wp_footer() {

		self::_hook_file(__FUNCTION__);

	}

	/**
	 * inserts a place holder HTML comment on `after_wp_footer` hook then injects the hook file content
	 *
	 * @access public
	 * @return void
	 * @internal look for _hooks/after_wp_footer.php
	 */
	public static function after_wp_footer() {

		self::_hook_file(__FUNCTION__);

	}

	/**
	 * starts the output buffering process w/ a callback
	 *
	 * @access public
	 * @param null
	 * @return void
	 * @internal add_action( 'init', array( __CLASS__, 'buffer_start' ), 11 );
	 */
	public static function buffer_start() {
		
		ob_start(array(__CLASS__, 'tidy_html'));

	}

	/**
	 * ends the output buffering process
	 *
	 * @access public
	 * @param null
	 * @return void
	 * @internal add_action( 'init', array( __CLASS__, 'buffer_start' ), 11 );
	 */
	public static function buffer_end() {

		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

	}

	/**
	 * runs various functions to alter the output buffer before sendint it to the browser
	 *
	 * @access public
	 * @param string $buffer
	 * @return string $buffer
	 * @internal self::$buffer_start()
	 */
	public static function tidy_html( $buffer ) {

		if ( is_feed() ) {
			return $buffer;
		}

		// error_log( json_encode(self::$configs) );
		//
		$assets = self::$configs['assets'];
		$type   = 'css';

		if ( isset(self::$configs['assets'][$type]['inlines']) &&
			self::$configs['assets'][$type]['inlines'] ) {
				$inlines = '';
				$inlines = implode("\n", self::$configs['assets'][$type]['inlines']);
				$replace = self::$configs['assets'][$type]['config']['relocate'];
				$buffer  = strtr($buffer, array($replace => "{$replace}\n{$inlines}\n"));
		}

		return $buffer;

	}

	/**
	 * simply returns our debug array
	 * 
	 * @access public
	 * @return array $debug
	 */
	public static function debug() {
		
		return self::$debug;

	}

	/**
	 * use set_x and get_x functions to pass arguments within template parts
	 * 
	 * @see _parts/navigation.php as an example
	 */
	
	/**
	 * sets a single argument based on $name and $value
	 *
	 * @access public
	 * @param string $name  name of argument name
	 * @param mixed  $value value
	 * @return void
	 */
	public static function set_var( $name, $value ) {

		if ( !$name ) {
			return;
		}

		self::$template_vars[$name] = $value;

	}

	/**
	 * sets multiple arguments
	 *
	 * @access public
	 * @param array $args array of mixed name => values
	 * @return null if $args is malformed or void
	 */
	public static function set_vars( $args ) {

		if ( !$args ) {
			return;
		}

		foreach( $args as $name => $value ) {
			self::$template_vars[$name] = $value;
		}

	}

	/**
	 * returns $template_vars;
	 * 
	 * @access public
	 * @return array $template_vars template variables
	 */
	public static function get_vars() {

		return self::$template_vars;

	}

	/**
	 * fixes up the author archives permalink for our built in post type `post`
	 *
	 * @param (string) $link
	 * @param (string) $author_id
	 * @param (string) $author_nicename
	 * @internal add_filter( 'author_link', array( __CLASS__, 'author_link' ), 10, 3 )
	 */
	public static function author_link( $link, $author_id, $author_nicename ) {

		global $post, $wp, $wp_query, $wp_rewrite;

		if ( $wp_rewrite->front != '/' ) {

			/* if your front page is set to the blog index, the value is '/' */
			$_front	= $wp_rewrite->front;
			$_front	= ($_front[0] == '/') ? substr($_front, 1) : $_front;
			$_front	= ($_front[strlen($_front)-1] == '/') ? untrailingslashit($_front) : $front;

			$_frt	= explode( "/", $_front );
			$_req	= explode( "/", $wp->request );

			if ( reset($_frt) == reset($_req) ) {
				$components   = array();
				$components[] = $_front;
				$components[] = $wp_rewrite->author_base;
				$components[] = $author_nicename;

				unset( $_front, $frt, $_req );

				return home_url( user_trailingslashit( implode( "/", $components ) ) );
			}

		}

		return $link;

	}


}

T::get_instance();