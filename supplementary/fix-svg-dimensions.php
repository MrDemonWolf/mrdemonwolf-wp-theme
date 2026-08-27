<?php
/**
 * Backfill width/height metadata for SVG attachments.
 *
 * WordPress cannot read SVG dimensions, so every SVG imports with 0x0
 * metadata. Divi then resolves the attachment behind an image module -- by id,
 * or by URL when the id is blank -- and emits width="0" height="0" with a
 * "0w" srcset, which collapses the image to nothing. The footer logo rendered
 * invisible exactly this way; the header logo only survived because the
 * stylesheet pins its width.
 *
 * Dimensions come from the SVG's own viewBox (falling back to width/height
 * attributes). Idempotent: attachments that already carry a width are skipped.
 *
 * Usage: wp eval-file supplementary/fix-svg-dimensions.php
 */

$svgs = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image/svg+xml',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

$fixed   = 0;
$skipped = 0;

foreach ( $svgs as $svg ) {
	$meta = wp_get_attachment_metadata( $svg->ID );
	if ( ! empty( $meta['width'] ) ) {
		$skipped++;
		continue;
	}

	$file = get_attached_file( $svg->ID );
	if ( ! $file || ! file_exists( $file ) ) {
		echo "  missing file for attachment {$svg->ID}\n";
		continue;
	}

	$head = file_get_contents( $file, false, null, 0, 4096 );
	$w    = 0;
	$h    = 0;

	if ( preg_match( '/viewBox=["\']([\d.\s eE+-]+)["\']/', $head, $m ) ) {
		$parts = preg_split( '/[\s,]+/', trim( $m[1] ) );
		if ( 4 === count( $parts ) ) {
			$w = (int) round( (float) $parts[2] );
			$h = (int) round( (float) $parts[3] );
		}
	}
	if ( ( ! $w || ! $h ) && preg_match( '/<svg[^>]*\swidth=["\']?([\d.]+)/', $head, $mw )
		&& preg_match( '/<svg[^>]*\sheight=["\']?([\d.]+)/', $head, $mh ) ) {
		$w = (int) round( (float) $mw[1] );
		$h = (int) round( (float) $mh[1] );
	}

	if ( ! $w || ! $h ) {
		echo "  no dimensions found in {$svg->post_title} ({$svg->ID})\n";
		continue;
	}

	$meta           = is_array( $meta ) ? $meta : array();
	$meta['width']  = $w;
	$meta['height'] = $h;
	$meta['file']   = _wp_relative_upload_path( $file );
	wp_update_attachment_metadata( $svg->ID, $meta );
	echo "  {$svg->post_title} ({$svg->ID}) -> {$w}x{$h}\n";
	$fixed++;
}

echo "svg dimensions: fixed {$fixed}, already ok {$skipped}\n";

// Divi memoises attachment dimensions in its Post Features cache, which
// survives et-cache deletion and `wp cache flush` -- with it intact the
// corrected metadata above never reaches the rendered markup.
if ( $fixed > 0 ) {
	global $wpdb;
	$purged = $wpdb->query(
		"DELETE FROM {$wpdb->postmeta}
		 WHERE meta_key LIKE '%et_builder%cache%'
		    OR meta_key LIKE '%_et_builder_module_features%'"
	);
	echo "divi post-features cache rows purged: {$purged}\n";
}
