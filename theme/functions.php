<?php
//Activation of the child theme
function theme_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	// Magnific Popup (loaded from local assets)
	wp_enqueue_style('magnific-popup-css', get_stylesheet_directory_uri() . '/assets/magnific-popup.min.css', [], '1.1.0', 'all');
	wp_enqueue_script('magnific-popup-js', get_stylesheet_directory_uri() . '/assets/jquery.magnific-popup.min.js', ['jquery'], '1.1.0', true);

	// Main script
	wp_enqueue_script('mrdemonwolf-script', get_stylesheet_directory_uri() . '/script.js', ['jquery', 'magnific-popup-js'], '1.0.0', true);
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

//Deleting the Wordpress version number
function dc_delete_version() {
  return '';
}
add_filter('the_generator', 'dc_delete_version');

//Hide adminsitration login errors
function wpm_hide_errors() {
	return "The username or password is incorrect";
}
add_filter('login_errors', 'wpm_hide_errors');

// Allow SVG in WordPress Importer
function allow_svg_in_importer( $data, $file, $filename, $mimes ) {

    if ( strpos( $filename, '.svg' ) !== false ) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }

    return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'allow_svg_in_importer', 10, 4 );

// Allow SVG mime
function allow_svg_mime( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'allow_svg_mime' );

// Disable year/month uploads folders
function disable_year_month_uploads() {
    update_option( 'uploads_use_yearmonth_folders', 0 );
}
add_action( 'init', 'disable_year_month_uploads' );

// ===============================
// Register "Service" Custom Post Type
// ===============================
function mrdemonwolf_register_service_cpt() {

    $labels = array(
        'name'               => 'Services',
        'singular_name'      => 'Service',
        'menu_name'          => 'Services',
        'add_new_item'       => 'Add New Service',
        'edit_item'          => 'Edit Service',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon'          => 'dashicons-image-filter',
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'services'),
        'show_in_rest'       => true,
    );

    register_post_type('service', $args);
}
add_action('init', 'mrdemonwolf_register_service_cpt');

// Add custom image field
function mrdemonwolf_service_add_metabox() {
    add_meta_box(
        'mrdemonwolf_service_custom_image',
        'Icon',
        'mrdemonwolf_service_custom_image_callback',
        'service',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mrdemonwolf_service_add_metabox');

function mrdemonwolf_service_custom_image_callback($post) {

    wp_nonce_field('mrdemonwolf_service_image_nonce', 'mrdemonwolf_service_image_nonce_field');

    $image_url = get_post_meta($post->ID, '_mrdemonwolf_service_image', true);

    ?>
    <div>
        <img id="mrdemonwolf-service-image-preview"
             src="<?php echo esc_url($image_url); ?>"
             style="max-width:100%;<?php echo $image_url ? '' : 'display:none;'; ?>" />

        <input type="hidden" id="mrdemonwolf-service-image" name="mrdemonwolf_service_image" value="<?php echo esc_attr($image_url); ?>">

        <button type="button" class="button" id="mrdemonwolf-service-upload-btn">Select Image</button>
        <button type="button" class="button" id="mrdemonwolf-service-remove-btn" style="<?php echo $image_url ? '' : 'display:none;'; ?>">Remove</button>
    </div>

    <script>
    jQuery(function($){
        var frame;

        $('#mrdemonwolf-service-upload-btn').on('click', function(e){
            e.preventDefault();

            if(frame){ frame.open(); return; }

            frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#mrdemonwolf-service-image').val(attachment.url);
                $('#mrdemonwolf-service-image-preview').attr('src', attachment.url).show();
                $('#mrdemonwolf-service-remove-btn').show();
            });

            frame.open();
        });

        $('#mrdemonwolf-service-remove-btn').on('click', function(){
            $('#mrdemonwolf-service-image').val('');
            $('#mrdemonwolf-service-image-preview').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Save the field as URL
function mrdemonwolf_service_save_image($post_id) {
	if (
    ! isset( $_POST['mrdemonwolf_service_image_nonce_field'] )
    || ! wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image_nonce_field'] ) ),
        'mrdemonwolf_service_image_nonce'
    )
) {
    return;
}
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if(isset($_POST['mrdemonwolf_service_image'])){
        update_post_meta($post_id, '_mrdemonwolf_service_image', esc_url_raw($_POST['mrdemonwolf_service_image']));
    }
}
add_action('save_post_service', 'mrdemonwolf_service_save_image');

// ===============================
// Breadcrumbs Shortcode
// ===============================
function mrdemonwolf_breadcrumbs_shortcode($atts) {
	if (is_admin() && !wp_doing_ajax()) {
		return '';
	}

	global $post;

	if (!$post) {
		return '';
	}

	$atts = shortcode_atts(['home' => 'Home'], $atts, 'mrdemonwolf_breadcrumbs');
	$breadcrumb = '<nav class="mdw-breadcrumbs" aria-label="Breadcrumb">';
	$breadcrumb .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($atts['home']) . '</a>';

	if (function_exists('is_woocommerce') && is_woocommerce()) {
		if (is_singular('product')) {
			$terms = get_the_terms($post->ID, 'product_cat');
			if (!empty($terms) && !is_wp_error($terms)) {
				$term = reset($terms);
				$breadcrumb .= ' <span class="mdw-separator"> > </span>';
				$breadcrumb .= '<a href="' . esc_url(get_term_link($term)) . '"' . esc_html($term->name) . '</a>';
			}
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

		} elseif (is_tax('product_cat')) {
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . single_term_title('', false) . '</span>';
		} elseif (is_shop()) {
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title(wc_get_page_id('shop'))) . '</span>';
		}
	} elseif (is_single() && 'post' === get_post_type()) {
		$categories = get_the_category($post->ID);
		if (!empty($categories)) {
			$breadcrumb .= ' <span class="mdw-separator"></span><a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . esc_html($categories[0]->name) . '</a>';
		}
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

	} elseif (is_single() && 'project' === get_post_type()) {
		$terms = get_the_terms($post->ID, 'project_category');
		if (!empty($terms) && !is_wp_error($terms)) {
			$term = reset($terms);
			$breadcrumb .= ' <span class="mdw-separator"></span>';
			$breadcrumb .= '<a href="' . esc_url(get_term_link($term->term_id, 'project_category')) . '">' . esc_html($term->name) . '</a>';
		}
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';
	} elseif (is_page()) {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

	} elseif (is_category()) {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . single_cat_title('', false) . '</span>';
	} else {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' .  preg_replace('/^.*?:\s*/', '', get_the_archive_title()) . '</span>';
	}

	return $breadcrumb . '</nav>';
}
add_shortcode('mrdemonwolf_breadcrumbs', 'mrdemonwolf_breadcrumbs_shortcode');


// ===============================
// Current Post Tags Shortcode
// ===============================
function mrdemonwolf_tags_shortcode() {
	$post_id = get_the_ID();
	if (!$post_id) return '';

	$post_type = get_post_type($post_id);

	// If it's a "project", use its custom taxonomy
	if ($post_type === 'project') {
		$tags = get_the_terms($post_id, 'project_tag');
	} else {
		$tags = get_the_tags($post_id);
	}

	if (empty($tags) || is_wp_error($tags)) return '';

	$html = '';
	foreach ($tags as $tag) {
		$html .= sprintf(
			'<a class="mdw-tags" href="%s">%s</a>',
			esc_url(get_term_link($tag->term_id)),
			esc_html($tag->name)
		);
	}

	return $html;
}
add_shortcode('mrdemonwolf_tags', 'mrdemonwolf_tags_shortcode');

// ===============================
// Social Share Shortcode
// ===============================
add_shortcode('mrdemonwolf_social_share', function() {
    $url   = urlencode(get_permalink());
    $title = urlencode(get_the_title());
	$buttons = "";

    // Facebook
    $buttons .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '" target="_blank" rel="noopener">&#xe093;</a>';
    // Twitter / X
    $buttons .= '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank" rel="noopener">&#xe094;</a>';
    // LinkedIn
    $buttons .= '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $url . '" target="_blank" rel="noopener">&#xe09d;</a>';

    return $buttons;
});