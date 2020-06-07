<?php
/**
 * Uninstall plugin.
 *
 * This file is executed when the plugin is uninstalled
 * and will remove all stored plugin settings.
 *
 * @package better-images
 */

// Die if uninstall is not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'wnbi_better_images_version' );
delete_option( 'wnbi_better_images_resize_threshold' );
delete_option( 'wnbi_better_images_quality' );
delete_option( 'wnbi_better_images_resize_image' );
delete_option( 'wnbi_better_images_sharpen_image' );
delete_option( 'wnbi_better_images_remove_exif' );
delete_option( 'wnbi_better_images_convert_png' );
delete_option( 'wnbi_better_images_convert_cmyk' );
