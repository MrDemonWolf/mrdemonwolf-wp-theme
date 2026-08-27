<?php
/**
 * Attach imported Theme Builder templates to Divi's Theme Builder container.
 *
 * Divi does not find templates by post_parent. The container post holds a
 * repeating `_et_template` meta key listing the template post IDs, and
 * et_theme_builder_get_theme_builder_template_ids() reads only that. A WXR
 * import creates the template posts but never that meta, so Divi sees a
 * container with no templates and renders the site with no Theme Builder
 * header, footer or archive layouts.
 *
 * The failure is quiet and easy to misdiagnose: every page still returns 200,
 * it just loses whole sections and the design classes that go with them, which
 * reads as broken CSS rather than as unassigned templates.
 */
if ( ! function_exists( 'et_theme_builder_get_theme_builder_post_id' ) ) {
    fwrite( STDERR, "Divi's Theme Builder API is unavailable; is Divi active?\n" );
    exit( 1 );
}

$container = (int) et_theme_builder_get_theme_builder_post_id( true );

if ( ! $container ) {
    fwrite( STDERR, "Could not resolve the Theme Builder container post.\n" );
    exit( 1 );
}

$templates = get_posts(
    [
        'post_type'   => 'et_template',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby'     => 'ID',
        'order'       => 'ASC',
    ]
);

if ( ! $templates ) {
    fwrite( STDERR, "No et_template posts found; import All Content.xml first.\n" );
    exit( 1 );
}

$dangling = [];

foreach ( $templates as $template ) {
    // A layout id pointing at a post that no longer exists renders as a blank
    // area with no warning, so surface it rather than letting it look like CSS.
    foreach ( [ '_et_header_layout_id', '_et_body_layout_id', '_et_footer_layout_id' ] as $key ) {
        $layout_id = (int) get_post_meta( $template->ID, $key, true );
        if ( $layout_id && ! get_post( $layout_id ) ) {
            $dangling[] = "{$template->post_title}: {$key} -> {$layout_id}";
        }
    }
}

// Rebuild the list from scratch so re-running cannot accumulate duplicates.
delete_post_meta( $container, '_et_template' );

foreach ( $templates as $template ) {
    add_post_meta( $container, '_et_template', $template->ID );
}

// A stale backup of the template list takes precedence over the meta.
delete_option( 'et_tb_templates_backup_' . $container );

$linked = get_post_meta( $container, '_et_template', false );

printf( "  container:        %d\n", $container );
printf( "  templates linked: %d\n", count( $linked ) );

$resolved = et_theme_builder_get_theme_builder_templates( true );
printf( "  Divi resolves:    %d\n", count( $resolved ) );

if ( count( $resolved ) !== count( $templates ) ) {
    fwrite( STDERR, "  Divi resolved fewer templates than exist.\n" );
    exit( 1 );
}

if ( $dangling ) {
    echo "  DANGLING LAYOUT REFERENCES:\n";
    foreach ( $dangling as $d ) {
        echo "    $d\n";
    }
    exit( 1 );
}
