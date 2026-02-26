<?php
/**
 * MrDemonWolf Theme Migration Script
 *
 * Migrates data from the Nexus Divi Child Theme to MrDemonWolf.
 * Designed for use with WP-CLI: wp eval-file migrate.php [--dry-run]
 *
 * @package MrDemonWolf
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This script must be run via WP-CLI: wp eval-file migrate.php [--dry-run]\n";
	exit( 1 );
}

global $wpdb;

$dry_run = in_array( '--dry-run', $GLOBALS['argv'] ?? [], true );

if ( $dry_run ) {
	echo "=== DRY RUN MODE — no changes will be made ===\n\n";
} else {
	echo "=== LIVE MODE — changes will be applied ===\n";
	echo "WARNING: Back up your database before proceeding!\n\n";
}

$total_changes = 0;

// ---------------------------------------------------------------------------
// 1. Shortcodes in wp_posts.post_content
// ---------------------------------------------------------------------------
$shortcode_map = [
	'Nexus_breadcrumbs' => 'mrdemonwolf_breadcrumbs',
	'nexus_tags'        => 'mrdemonwolf_tags',
	'nexus_social_share' => 'mrdemonwolf_social_share',
];

echo "--- Shortcode migration ---\n";
foreach ( $shortcode_map as $old => $new ) {
	$count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s",
		'%' . $wpdb->esc_like( "[{$old}" ) . '%'
	) );

	echo "  [{$old}] → [{$new}]: {$count} post(s) affected\n";
	$total_changes += $count;

	if ( ! $dry_run && $count > 0 ) {
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
			"[{$old}",
			"[{$new}",
			'%' . $wpdb->esc_like( "[{$old}" ) . '%'
		) );
	}
}

// ---------------------------------------------------------------------------
// 2. CSS classes nexus- → mdw- in post_content (Divi builder data)
// ---------------------------------------------------------------------------
echo "\n--- CSS class migration (nexus- → mdw-) ---\n";
$css_count = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s",
	'%' . $wpdb->esc_like( 'nexus-' ) . '%'
) );
echo "  nexus- → mdw-: {$css_count} post(s) affected\n";
$total_changes += $css_count;

if ( ! $dry_run && $css_count > 0 ) {
	$wpdb->query(
		"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'nexus-', 'mdw-') WHERE post_content LIKE '%nexus-%'"
	);
}

// ---------------------------------------------------------------------------
// 3. HTML IDs nexus-header-btn → mdw-header-btn in post_content
// ---------------------------------------------------------------------------
echo "\n--- HTML ID migration ---\n";
$id_count = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s",
	'%' . $wpdb->esc_like( 'nexus-header-btn' ) . '%'
) );
echo "  nexus-header-btn → mdw-header-btn: {$id_count} post(s) affected\n";
$total_changes += $id_count;

if ( ! $dry_run && $id_count > 0 ) {
	$wpdb->query(
		"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'nexus-header-btn', 'mdw-header-btn') WHERE post_content LIKE '%nexus-header-btn%'"
	);
}

// ---------------------------------------------------------------------------
// 4. Post meta keys _nexus_service_image → _mrdemonwolf_service_image
// ---------------------------------------------------------------------------
echo "\n--- Post meta migration ---\n";
$meta_count = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
	'_nexus_service_image'
) );
echo "  _nexus_service_image → _mrdemonwolf_service_image: {$meta_count} row(s)\n";
$total_changes += $meta_count;

if ( ! $dry_run && $meta_count > 0 ) {
	$wpdb->update(
		$wpdb->postmeta,
		[ 'meta_key' => '_mrdemonwolf_service_image' ],
		[ 'meta_key' => '_nexus_service_image' ]
	);
}

// ---------------------------------------------------------------------------
// 5. wp_options entries with nexus_ prefix
// ---------------------------------------------------------------------------
echo "\n--- Options migration ---\n";
$options = $wpdb->get_results( $wpdb->prepare(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
	$wpdb->esc_like( 'nexus_' ) . '%'
) );

$opt_count = count( $options );
echo "  nexus_* options found: {$opt_count}\n";
$total_changes += $opt_count;

if ( ! $dry_run && $opt_count > 0 ) {
	foreach ( $options as $opt ) {
		$new_name = preg_replace( '/^nexus_/', 'mrdemonwolf_', $opt->option_name );
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_name = %s WHERE option_name = %s",
			$new_name,
			$opt->option_name
		) );
		echo "    Renamed: {$opt->option_name} → {$new_name}\n";
	}
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n=== Summary ===\n";
echo "Total items to migrate: {$total_changes}\n";

if ( $dry_run ) {
	echo "No changes were made (dry-run mode).\n";
	echo "To apply changes, run: wp eval-file migrate.php\n";
} else {
	echo "All changes applied successfully.\n";
	echo "This script is idempotent — safe to run again.\n";
}
