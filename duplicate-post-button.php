<?php
/**
 * Plugin Name:       Duplicate Post Button
 * Plugin URI:        https://github.com/jdyach/duplicate-post-button
 * Description:       Adds a one-click "Duplicate" row action to the Posts and Pages list screen. Clones the post content, meta, and taxonomy terms as a new draft.
 * Version:           1.0.0
 * Author:            Jacqui Dyach
 * Author URI:        https://jacquid.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       duplicate-post-button
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DPB_VERSION',    '1.0.0' );
define( 'DPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DPB_PLUGIN_DIR . 'includes/class-dpb-duplicator.php';

function dpb_init(): void {
    DPB_Duplicator::init();
}
add_action( 'plugins_loaded', 'dpb_init' );

/**
 * Enqueue admin stylesheet on post list screens.
 */
function dpb_enqueue_admin_assets( string $hook_suffix ): void {
    if ( ! in_array( $hook_suffix, [ 'edit.php' ], true ) ) {
        return;
    }
    wp_enqueue_style(
        'dpb-admin',
        DPB_PLUGIN_URL . 'assets/css/admin.css',
        [],
        DPB_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'dpb_enqueue_admin_assets' );
