<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load translations
function mrdemonwolf_load_textdomain() {
	load_child_theme_textdomain( 'mrdemonwolf', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'mrdemonwolf_load_textdomain' );

// Activation of the child theme
function mrdemonwolf_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( get_template() )->get( 'Version' ) );

	// Magnific Popup (loaded locally)
	wp_enqueue_style( 'magnific-popup-css', get_stylesheet_directory_uri() . '/assets/magnific-popup.min.css', array(), '1.1.0', 'all' );
	wp_enqueue_script( 'magnific-popup-js', get_stylesheet_directory_uri() . '/assets/jquery.magnific-popup.min.js', array( 'jquery' ), '1.1.0', true );

	// Main script
	wp_enqueue_script( 'mrdemonwolf-script', get_stylesheet_directory_uri() . '/script.js', array( 'jquery', 'magnific-popup-js' ), (string) filemtime( get_stylesheet_directory() . '/script.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'mrdemonwolf_enqueue_styles' );

//Deleting the Wordpress version number
function mrdemonwolf_delete_version() {
	return '';
}
add_filter( 'the_generator', 'mrdemonwolf_delete_version' );

// Hide administration login errors
function mrdemonwolf_hide_login_errors() {
	return __( 'The username or password is incorrect', 'mrdemonwolf' );
}
add_filter( 'login_errors', 'mrdemonwolf_hide_login_errors' );

// SVG upload/rendering is handled by the SVG Support plugin.
// @see https://wordpress.org/plugins/svg-support/

// Disable year/month uploads folders
function mrdemonwolf_disable_year_month_uploads() {
	update_option( 'uploads_use_yearmonth_folders', 0 );
}
add_action( 'after_switch_theme', 'mrdemonwolf_disable_year_month_uploads' );

// Resolve the mu-plugins directory with a sensible fallback.
function mrdemonwolf_mu_dir() {
	return defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
}

// ===============================
// Theme Cleanup on Switch
// ===============================
function mrdemonwolf_on_theme_switch() {
	// Hardened hosts and security plugins block runtime file writes.
	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return;
	}

	$mu_dir  = mrdemonwolf_mu_dir();
	$mu_file = $mu_dir . '/mdw-cleanup-notice.php';

	if ( ! is_dir( $mu_dir ) ) {
		wp_mkdir_p( $mu_dir );
	}

	$mu_code = <<<'PHP'
<?php
/**
 * Plugin Name: MrDemonWolf Cleanup Notice
 * Description: One-time admin notice to clean up MrDemonWolf theme data after deactivation.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_notices', 'mdw_cleanup_admin_notice' );
function mdw_cleanup_admin_notice() {
	$nonce = wp_create_nonce( 'mdw_cleanup_action' );
	?>
	<div class="notice notice-warning is-dismissible" id="mdw-cleanup-notice">
		<p><strong><?php echo esc_html__( 'MrDemonWolf theme was deactivated.', 'mrdemonwolf' ); ?></strong>
		<?php echo esc_html__( 'Clean up theme data?', 'mrdemonwolf' ); ?></p>
		<p>
			<button class="button button-primary" id="mdw-cleanup-btn"><?php echo esc_html__( 'Clean Up', 'mrdemonwolf' ); ?></button>
			<button class="button" id="mdw-dismiss-btn"><?php echo esc_html__( 'Dismiss', 'mrdemonwolf' ); ?></button>
		</p>
	</div>
	<script>
	(function(){
		function mdwCleanupAjax(action) {
			var data = new FormData();
			data.append('action', 'mdw_cleanup_theme_data');
			data.append('cleanup', action);
			data.append('nonce', '<?php echo esc_js( $nonce ); ?>');
			fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function(r){ return r.json(); })
				.then(function(){ document.getElementById('mdw-cleanup-notice').remove(); });
		}
		document.getElementById('mdw-cleanup-btn').addEventListener('click', function(){ mdwCleanupAjax('clean'); });
		document.getElementById('mdw-dismiss-btn').addEventListener('click', function(){ mdwCleanupAjax('dismiss'); });
	})();
	</script>
	<?php
}

add_action( 'wp_ajax_mdw_cleanup_theme_data', 'mdw_cleanup_theme_data_handler' );
function mdw_cleanup_theme_data_handler() {
	check_ajax_referer( 'mdw_cleanup_action', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions.' );
	}

	$cleanup = isset( $_POST['cleanup'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup'] ) ) : '';

	if ( 'clean' === $cleanup ) {
		update_option( 'uploads_use_yearmonth_folders', 1 );
		delete_post_meta_by_key( '_mrdemonwolf_service_image' );
		flush_rewrite_rules();
	}

	// Delete this mu-plugin file regardless of clean/dismiss
	$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
	$self   = realpath( $mu_dir . '/mdw-cleanup-notice.php' );
	if ( $self && strpos( $self, realpath( $mu_dir ) ) === 0 && file_exists( $self ) ) {
		unlink( $self );
	}

	wp_send_json_success();
}
PHP;

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	if ( ! $wp_filesystem || ! $wp_filesystem->is_writable( $mu_dir ) ) {
		error_log( 'MrDemonWolf: mu-plugins is not writable, skipped the cleanup notice.' );
		return;
	}

	if ( ! $wp_filesystem->put_contents( $mu_file, $mu_code, FS_CHMOD_FILE ) ) {
		error_log( 'MrDemonWolf: failed to write cleanup mu-plugin to ' . $mu_file );
	}
}
add_action( 'switch_theme', 'mrdemonwolf_on_theme_switch' );

// ===============================
// Register "Service" Custom Post Type
// ===============================
function mrdemonwolf_register_service_cpt() {

	$labels = array(
		'name'               => __( 'Services', 'mrdemonwolf' ),
		'singular_name'      => __( 'Service', 'mrdemonwolf' ),
		'menu_name'          => __( 'Services', 'mrdemonwolf' ),
		'add_new_item'       => __( 'Add New Service', 'mrdemonwolf' ),
		'edit_item'          => __( 'Edit Service', 'mrdemonwolf' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'menu_icon'          => 'dashicons-image-filter',
		'has_archive'        => true,
		'rewrite'            => array( 'slug' => 'services' ),
		'show_in_rest'       => true,
	);

	register_post_type( 'service', $args );
}
add_action( 'init', 'mrdemonwolf_register_service_cpt' );

// Add custom image field
function mrdemonwolf_service_add_metabox() {
	add_meta_box(
		'mrdemonwolf_service_custom_image',
		__( 'Icon', 'mrdemonwolf' ),
		'mrdemonwolf_service_custom_image_callback',
		'service',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'mrdemonwolf_service_add_metabox' );

// Enqueue media picker script on the service edit screen only.
function mrdemonwolf_service_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'service' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'mrdemonwolf-service-metabox',
		get_stylesheet_directory_uri() . '/assets/admin-service-metabox.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);
	wp_localize_script(
		'mrdemonwolf-service-metabox',
		'mdwServiceMetabox',
		array(
			'title'      => __( 'Select Image', 'mrdemonwolf' ),
			'buttonText' => __( 'Use this image', 'mrdemonwolf' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'mrdemonwolf_service_admin_assets' );

function mrdemonwolf_service_custom_image_callback( $post ) {

	wp_nonce_field( 'mrdemonwolf_service_image_nonce', 'mrdemonwolf_service_image_nonce_field' );

	$image_url = get_post_meta( $post->ID, '_mrdemonwolf_service_image', true );

	?>
	<div>
		<img id="mdw-service-image-preview"
			 src="<?php echo esc_url( $image_url ); ?>"
			 style="max-width:100%;<?php echo $image_url ? '' : 'display:none;'; ?>" />

		<input type="hidden" id="mdw-service-image" name="mrdemonwolf_service_image" value="<?php echo esc_attr( $image_url ); ?>">

		<button type="button" class="button" id="mdw-service-upload-btn"><?php esc_html_e( 'Select Image', 'mrdemonwolf' ); ?></button>
		<button type="button" class="button" id="mdw-service-remove-btn" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'mrdemonwolf' ); ?></button>
	</div>
	<?php
}

// Save the field as URL
function mrdemonwolf_service_save_image( $post_id ) {
	if (
		! isset( $_POST['mrdemonwolf_service_image_nonce_field'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image_nonce_field'] ) ),
			'mrdemonwolf_service_image_nonce'
		)
	) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['mrdemonwolf_service_image'] ) ) {
		$image_url = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image'] ) ) );
		update_post_meta( $post_id, '_mrdemonwolf_service_image', $image_url );
	}
}
add_action( 'save_post_service', 'mrdemonwolf_service_save_image' );

// ===============================
// Breadcrumbs Shortcode
// ===============================
function mrdemonwolf_breadcrumb_sep() {
	return ' <span class="mdw-separator"></span>';
}

// Return a crumb for the first term of $taxonomy on $post_id, or null.
function mrdemonwolf_primary_term_crumb( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	$term = reset( $terms );
	$link = get_term_link( $term->term_id, $taxonomy );
	if ( is_wp_error( $link ) ) {
		return null;
	}

	return array(
		'label' => $term->name,
		'url'   => $link,
	);
}

// Collect the trail as data once; the markup and the schema both render from it.
function mrdemonwolf_breadcrumb_trail( $home_label ) {
	global $post;

	$crumbs = array(
		array(
			'label' => $home_label,
			'url'   => home_url( '/' ),
		),
	);

	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		if ( is_singular( 'product' ) ) {
			$crumbs[] = mrdemonwolf_primary_term_crumb( $post->ID, 'product_cat' );
			$crumbs[] = array( 'label' => get_the_title() );
		} elseif ( is_tax( 'product_cat' ) ) {
			$crumbs[] = array( 'label' => single_term_title( '', false ) );
		} elseif ( is_shop() ) {
			$crumbs[] = array( 'label' => get_the_title( wc_get_page_id( 'shop' ) ) );
		}
	} elseif ( is_single() && 'post' === get_post_type() ) {
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$crumbs[] = array(
				'label' => $categories[0]->name,
				'url'   => get_category_link( $categories[0]->term_id ),
			);
		}
		$crumbs[] = array( 'label' => get_the_title() );

	} elseif ( is_single() && 'project' === get_post_type() ) {
		// The project CPT and its taxonomies come from Divi, not this theme.
		$crumbs[] = mrdemonwolf_primary_term_crumb( $post->ID, 'project_category' );
		$crumbs[] = array( 'label' => get_the_title() );

	} elseif ( is_page() ) {
		$crumbs[] = array( 'label' => get_the_title() );

	} elseif ( is_category() ) {
		$crumbs[] = array( 'label' => single_cat_title( '', false ) );
	} else {
		$crumbs[] = array( 'label' => preg_replace( '/^.*?:\s*/', '', get_the_archive_title() ) );
	}

	return array_filter( $crumbs );
}

// BreadcrumbList JSON-LD, unless an SEO plugin already emits one.
function mrdemonwolf_breadcrumbs_schema( $crumbs ) {
	$emit = ! ( class_exists( '\RankMath\Helper' ) && \RankMath\Helper::get_settings( 'general.breadcrumbs' ) );

	/**
	 * Filter whether the breadcrumbs shortcode emits BreadcrumbList schema.
	 */
	if ( ! apply_filters( 'mrdemonwolf_breadcrumbs_schema', $emit ) ) {
		return '';
	}

	$items = array();
	foreach ( $crumbs as $i => $crumb ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['label'],
		);
		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}
		$items[] = $item;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	return '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
}

function mrdemonwolf_breadcrumbs_shortcode( $atts ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return '';
	}

	global $post;

	if ( ! $post ) {
		return '';
	}

	$atts   = shortcode_atts( array( 'home' => __( 'Home', 'mrdemonwolf' ) ), $atts, 'mrdemonwolf_breadcrumbs' );
	$crumbs = array_values( mrdemonwolf_breadcrumb_trail( $atts['home'] ) );

	$html = '<nav class="mdw-breadcrumbs" aria-label="Breadcrumb">';
	foreach ( $crumbs as $i => $crumb ) {
		if ( $i > 0 ) {
			$html .= mrdemonwolf_breadcrumb_sep();
		}
		if ( empty( $crumb['url'] ) ) {
			$html .= '<span>' . esc_html( $crumb['label'] ) . '</span>';
		} else {
			$html .= '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a>';
		}
	}
	$html .= '</nav>';

	return $html . mrdemonwolf_breadcrumbs_schema( $crumbs );
}
add_shortcode( 'mrdemonwolf_breadcrumbs', 'mrdemonwolf_breadcrumbs_shortcode' );


// ===============================
// Current Post Tags Shortcode
// ===============================
function mrdemonwolf_tags_shortcode() {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$post_type = get_post_type( $post_id );

	// If it's a "project", use its custom taxonomy
	if ( 'project' === $post_type ) {
		$tags = get_the_terms( $post_id, 'project_tag' );
	} else {
		$tags = get_the_tags( $post_id );
	}

	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return '';
	}

	$html = '';
	foreach ( $tags as $tag ) {
		$html .= sprintf(
			'<a class="mdw-tags" href="%s">%s</a>',
			esc_url( get_term_link( $tag->term_id ) ),
			esc_html( $tag->name )
		);
	}

	return $html;
}
add_shortcode( 'mrdemonwolf_tags', 'mrdemonwolf_tags_shortcode' );

// ===============================
// Social Share Shortcode
// ===============================
add_shortcode(
	'mrdemonwolf_social_share',
	function () {
		if ( ! get_the_ID() ) {
			return '';
		}

		$url   = rawurlencode( get_permalink() );
		$title = rawurlencode( get_the_title() );

		$platforms = array(
			array(
				'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
				'icon' => '&#xe093;',
			),
			array(
				'href' => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
				'icon' => '&#xe094;',
			),
			array(
				'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
				'icon' => '&#xe09d;',
			),
		);

		$buttons = '';
		foreach ( $platforms as $p ) {
			$buttons .= '<a href="' . esc_url( $p['href'] ) . '" target="_blank" rel="noopener">' . $p['icon'] . '</a>';
		}

		return $buttons;
	}
);

// ===============================
// Theme Updates from GitHub Releases
// ===============================
// WordPress routes the `Update URI:` header in style.css to the filter below,
// so the theme updates in place from Appearance > Themes. No plugin, no
// credentials: the repository is public and the release workflow attaches
// mrdemonwolf.zip to every tag.
function mrdemonwolf_github_release() {
	$release = get_site_transient( 'mrdemonwolf_latest_release' );
	if ( false !== $release ) {
		return $release;
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/MrDemonWolf/mrdemonwolf-wp-theme/releases/latest',
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'mrdemonwolf-wp-theme',
			),
		)
	);

	$release = array();

	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $data ) && ! empty( $data['tag_name'] ) && empty( $data['draft'] ) && empty( $data['prerelease'] ) ) {
			foreach ( (array) $data['assets'] as $asset ) {
				// The built zip only, never the source tarball: that one unpacks
				// to a versioned folder and would install as a second theme.
				if ( isset( $asset['name'] ) && 'mrdemonwolf.zip' === $asset['name'] ) {
					$release = array(
						'version' => ltrim( $data['tag_name'], 'v' ),
						'url'     => isset( $data['html_url'] ) ? $data['html_url'] : '',
						'package' => $asset['browser_download_url'],
					);
					break;
				}
			}
		}
	}

	// Cache misses too: a bad minute at GitHub must not mean an API call on
	// every update check. GitHub allows 60 unauthenticated requests an hour.
	set_site_transient( 'mrdemonwolf_latest_release', $release, 12 * HOUR_IN_SECONDS );

	return $release;
}

function mrdemonwolf_check_for_update( $update, $theme_data, $theme_stylesheet ) {
	$release = mrdemonwolf_github_release();

	if ( empty( $release['version'] ) || empty( $release['package'] ) ) {
		return $update;
	}

	$current = isset( $theme_data['Version'] ) ? $theme_data['Version'] : '';

	if ( '' === $current || version_compare( $release['version'], $current, '<=' ) ) {
		return $update;
	}

	return array(
		'theme'   => $theme_stylesheet,
		'version' => $release['version'],
		'url'     => $release['url'],
		'package' => $release['package'],
	);
}
add_filter( 'update_themes_github.com', 'mrdemonwolf_check_for_update', 10, 3 );

// Drop the cached release when an update finishes, so the screen stops
// advertising the version that was just installed.
function mrdemonwolf_clear_release_cache() {
	delete_site_transient( 'mrdemonwolf_latest_release' );
}
add_action( 'upgrader_process_complete', 'mrdemonwolf_clear_release_cache' );
add_action( 'switch_theme', 'mrdemonwolf_clear_release_cache' );
