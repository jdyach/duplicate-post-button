<?php
/**
 * Demo content for the WordPress Playground "Try it live" link.
 *
 * Runs once inside Playground: blueprint.json embeds this file (rebuild it
 * with `php bin/build-blueprint.php`). Creates posts with categories, tags, featured images and custom fields, then opens the Posts screen where each row has a Duplicate action.
 *
 * Not loaded by the plugin.
 *
 * @package Duplicate_Post_Button
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

wp_set_current_user( 1 );

// Start from a clean site: remove the default "Hello world!" post and sample page.
foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'numberposts' => -1, 'post_status' => 'any' ) ) as $demo_old ) {
	wp_delete_post( $demo_old->ID, true );
}

update_option( 'blogname', 'Duplicate Post Button' );
update_option( 'blogdescription', 'Live demo' );
// Pretty permalinks. Set them through WP_Rewrite, not update_option(): in
// Playground the routing rules already exist by now, and only this updates
// WordPress's in-memory copy so the flush rebuilds them correctly.
$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
flush_rewrite_rules();

/**
 * Upload one of the Fieldnote theme's images and return its attachment ID.
 *
 * @param string $name Image file name without extension.
 * @return int Attachment ID, or 0 when the theme image is unavailable.
 */
function demo_theme_image( $name ) {
	$source = get_theme_file_path( "assets/images/$name.webp" );
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( "$name.webp", null, file_get_contents( $source ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/webp',
			'post_title'     => $name,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	return $id;
}

/**
 * A long article in block markup: H2 sections with H3 subsections.
 *
 * @param array $sections Section titles.
 * @param int   $paras    Paragraphs per section.
 * @return string Block markup.
 */
function demo_article( $sections, $paras = 2 ) {
	$text = 'A good website is less about the launch and more about the years after it. The people editing it next need clear patterns, sensible defaults and documentation they will actually read. Small decisions made early, like naming, structure and what is configurable, decide how easy every later change will be.';
	$html = '';
	foreach ( $sections as $section ) {
		$html .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $section ) . '</h2><!-- /wp:heading -->';
		for ( $i = 0; $i < $paras; $i++ ) {
			$html .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
		}
		$html .= '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html( $section ) . ' checklist</h3><!-- /wp:heading -->';
		$html .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
	}
	return $html;
}

/**
 * Insert a published post or page.
 *
 * @param array $args wp_insert_post() arguments.
 * @return int Post ID.
 */
function demo_post( $args ) {
	return wp_insert_post( array_merge( array( 'post_status' => 'publish' ), $args ) );
}

/**
 * Write a small demo-only must-use plugin. These files exist only inside
 * the Playground demo; they are not part of the plugin.
 *
 * @param string $name File name.
 * @param string $code PHP source.
 */
function demo_mu_plugin( $name, $code ) {
	wp_mkdir_p( WPMU_PLUGIN_DIR );
	file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . $name, $code );
}

/** Skip the block editor's welcome guide so visitors land straight on the content. */
function demo_skip_welcome_guide( $extra = array() ) {
	update_user_meta(
		1,
		$GLOBALS['wpdb']->get_blog_prefix() . 'persisted_preferences',
		array_merge(
			array(
				'core/edit-post' => array( 'welcomeGuide' => false ),
				'core'           => array( 'welcomeGuide' => false ),
				'_modified'      => gmdate( 'c' ),
			),
			$extra
		)
	);
}

require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
$images = array_filter( array( demo_theme_image( 'studio-shapes' ), demo_theme_image( 'field-study' ), demo_theme_image( 'notebook' ) ) );
$cat    = wp_create_category( 'Case studies' );
foreach ( array( 'Hover me and click Duplicate', 'A post with tags and a featured image', 'Spring campaign landing page copy' ) as $i => $title ) {
	$id = demo_post(
		array(
			'post_title'    => $title,
			'post_content'  => demo_article( array( 'Overview', 'Details' ), 1 ),
			'post_category' => array( $cat ),
			'tags_input'    => array( 'demo', 'duplicate-me' ),
		)
	);
	update_post_meta( $id, 'demo_custom_field', 'Custom fields are copied too' );
	if ( $images ) {
		set_post_thumbnail( $id, $images[ $i % count( $images ) ] );
	}
}
