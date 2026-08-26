<?php
/**
 * Attach imported Theme Builder templates to Divi's Theme Builder container.
 *
 * A WXR import brings the et_template posts in flat, with post_parent = 0.
 * Divi expects them to be children of its et_theme_builder container post, and
 * when it finds none it quietly creates a fresh empty container instead. The
 * symptom is subtle and easy to misread: the site renders, but with no Theme
 * Builder header or footer and several body sections missing, which looks like
 * broken CSS rather than an unassigned template.
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
        'post_status' => 'any',
    ]
);

$moved = 0;
$relinked = 0;
$dangling = [];

foreach ( $templates as $template ) {
    if ( (int) $template->post_parent !== $container ) {
        wp_update_post(
            [
                'ID'          => $template->ID,
                'post_parent' => $container,
            ]
        );
        $moved++;
    }

    // The hierarchy is container -> template -> layout, and a WXR import
    // flattens all three levels. Reattaching only the templates gets the
    // Theme Builder as far as recognising them while every header, body and
    // footer layout still renders empty.
    foreach ( [ '_et_header_layout_id', '_et_body_layout_id', '_et_footer_layout_id' ] as $key ) {
        $layout_id = (int) get_post_meta( $template->ID, $key, true );
        if ( ! $layout_id ) {
            continue;
        }
        $layout = get_post( $layout_id );
        if ( $layout && (int) $layout->post_parent !== (int) $template->ID ) {
            wp_update_post(
                [
                    'ID'          => $layout_id,
                    'post_parent' => $template->ID,
                ]
            );
            $relinked++;
        }
    }

    // A layout id pointing at a post that no longer exists renders as a blank
    // area with no warning, so surface it rather than letting it look like CSS.
    foreach ( [ '_et_header_layout_id', '_et_body_layout_id', '_et_footer_layout_id' ] as $key ) {
        $layout_id = (int) get_post_meta( $template->ID, $key, true );
        if ( $layout_id && ! get_post( $layout_id ) ) {
            $dangling[] = "{$template->post_title}: {$key} -> {$layout_id}";
        }
    }
}

printf( "  container:          %d\n", $container );
printf( "  templates attached: %d of %d\n", $moved, count( $templates ) );
printf( "  layouts relinked:   %d\n", $relinked );

if ( $dangling ) {
    echo "  DANGLING LAYOUT REFERENCES:\n";
    foreach ( $dangling as $d ) {
        echo "    $d\n";
    }
    exit( 1 );
}
