<?php
/**
 * Plugin Name: Better Images
 * Description: A WordPress plugin for better images.
 * Version: 0.8.0
 * Text Domain: better-images
 * Domain Path: /languages
 * Author: Webbson AB
 * Author URI: https://webbson.se
 * License: GPLv2
 *
 * @package better-images
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

require 'imagick-helper.php';

$debug_logger   = false;
$plugin_version = '0.8.0';

// Default plugin values.
if ( is_admin() && ( get_option( 'bi_better_images_version' ) !== $plugin_version ) ) {
	if ( get_option( 'bi_better_images_version' ) !== false ) {
		update_option( 'bi_better_images_version', $plugin_version );
		tp_debug_log( 'Plugin has been updated.' );
	} else {
		add_option( 'bi_better_images_version', $plugin_version );
	}

	add_option( 'bi_better_images_resize_threshold', '2560' );
	add_option( 'bi_better_images_quality', '62' );
	add_option( 'bi_better_images_resize_image', 'yes' );
	add_option( 'bi_better_images_sharpen_image', 'yes' );
	add_option( 'bi_better_images_remove_exif', 'yes' );
	add_option( 'bi_better_images_convert_png', 'yes' );
	add_option( 'bi_better_images_convert_cmyk', 'yes' );
}

// Hook in the options page.
add_action( 'admin_menu', 'bi_better_images_options_page' );
add_action( 'admin_init', 'bi_better_images_admin_init' );

// Hook in all the filters.

add_filter( 'wp_handle_upload_prefilter', 'tp_validate_image' );
add_filter( 'sanitize_file_name', 'tp_sanitize_file_name', 10, 1 );
add_filter( 'big_image_size_threshold', 'tp_big_image_size_threshold' );
add_filter( 'image_make_intermediate_size', 'tp_sharpen_resized_files', 900 );
add_filter( 'wp_generate_attachment_metadata', 'tp_finialize_upload' );
add_filter( 'image_size_names_choose', 'tp_display_image_size_names_muploader', 11, 1 );

// Hook in all the actions.

add_action( 'wp_handle_upload', 'tp_handle_uploaded' );
add_action( 'plugins_loaded', 'better_images_load_plugin_textdomain' );

// Register activation hook.

register_activation_hook( __FILE__, 'bi_better_images_activate' );

/**
 * Load the plugin. Do check to see if imagick is installed.
 */
function bi_better_images_activate() {
	if ( ! extension_loaded( 'imagick' ) ) {
		die( esc_html__( 'Better Images could not be activated. The required PHP module ImageMagick could not be found.', 'better-images' ) );
	}
}

/**
 * Load text domain files.
 */
function better_images_load_plugin_textdomain() {
	load_plugin_textdomain( 'better-images', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

/**
 * Init styles.
 */
function bi_better_images_admin_init() {
	wp_register_style( 'biBetterImagesStylesheet', plugins_url( 'style.css', __FILE__ ) );
}

/**
 * Enqueue our stylesheet.
 */
function bi_better_images_admin_styles() {
	wp_enqueue_style( 'biBetterImagesStylesheet' );
}

/**
 * We leave the threshold off when re-sizing is enabled,
 * otherwise we set it to default setting.
 *
 * @param int $threshold Current threshold.
 */
function tp_big_image_size_threshold( $threshold ) {
	$resizing_enabled = ( get_option( 'bi_better_images_resize_image' ) === 'yes' ) ? true : false;

	if ( $resizing_enabled ) {
		return false;
	}

	return 2560;
}

/**
 * Add the options page.
 */
function bi_better_images_options_page() {
	global $bi_settings_page;
	if ( function_exists( 'add_options_page' ) ) {
		$bi_settings_page = add_options_page(
			'Better Images',
			'Better Images',
			'manage_options',
			'better-images',
			'bi_better_images_options'
		);

		add_action( "admin_print_styles-{$bi_settings_page}", 'bi_better_images_admin_styles' );
	}
}

/**
 * Define the options page for the plugin.
 */
function bi_better_images_options() {
	if ( isset( $_POST['bi-options-update'] ) && isset( $_POST['_wpnonce'] ) ) {

		$wpnonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

		if ( ! ( current_user_can( 'manage_options' )
			&& wp_verify_nonce( $wpnonce, 'bi-options-update' ) )
		) {
			wp_die( 'Not authorized' );
		}

		$resizing_enabled      = 'yes'; // ( 'yes' === $_POST['resize_yesno'] ? 'yes' : 'no' );
		$sharpen_image_enabled = ( 'yes' === $_POST['sharpen_yesno'] ? 'yes' : 'no' );
		$remove_exif_enabled   = ( 'yes' === $_POST['remove_exif_yesno'] ? 'yes' : 'no' );
		$convert_png_enabled   = ( 'yes' === $_POST['convert_png_yesno'] ? 'yes' : 'no' );
		$convert_cmyk_enabled  = ( 'yes' === $_POST['convert_cmyk_yesno'] ? 'yes' : 'no' );

		$compression_level = intval( $_POST['quality'] );

		$compression_level = ( '' === $compression_level ) ? 1 : $compression_level;
		$compression_level = ( ctype_digit( strval( $compression_level ) ) === false ) ? get_option( 'bi_better_images_quality' ) : $compression_level;

		if ( $compression_level < 1 ) {
			$compression_level = 1;
		} elseif ( $compression_level > 100 ) {
			$compression_level = 100;
		}

		if ( 'yes' === $resizing_enabled ) {
			update_option( 'bi_better_images_resize_image', 'yes' );
		} else {
			update_option( 'bi_better_images_resize_image', 'no' );
		}

		if ( 'yes' === $sharpen_image_enabled ) {
			update_option( 'bi_better_images_sharpen_image', 'yes' );
		} else {
			update_option( 'bi_better_images_sharpen_image', 'no' );
		}

		if ( 'yes' === $remove_exif_enabled ) {
			update_option( 'bi_better_images_remove_exif', 'yes' );
		} else {
			update_option( 'bi_better_images_remove_exif', 'no' );
		}

		if ( 'yes' === $convert_png_enabled ) {
			update_option( 'bi_better_images_convert_png', 'yes' );
		} else {
			update_option( 'bi_better_images_convert_png', 'no' );
		}

		if ( 'yes' === $convert_cmyk_enabled ) {
			update_option( 'bi_better_images_convert_cmyk', 'yes' );
		} else {
			update_option( 'bi_better_images_convert_cmyk', 'no' );
		}

		update_option( 'bi_better_images_quality', $compression_level );
		echo( '<div id="message" class="updated fade"><p><strong>Changes have been saved.</strong></p></div>' );
	}

	$compression_level     = intval( get_option( 'bi_better_images_quality' ) );
	$resizing_enabled      = get_option( 'bi_better_images_resize_image' );
	$sharpen_image_enabled = get_option( 'bi_better_images_sharpen_image' );
	$remove_exif_enabled   = get_option( 'bi_better_images_remove_exif' );
	$convert_png_enabled   = get_option( 'bi_better_images_convert_png' );
	$convert_cmyk_enabled  = get_option( 'bi_better_images_convert_cmyk' ); ?>


	<div class="wrap">
		<form method="post" accept-charset="utf-8">

			<div style="max-width: 620px;">
				<h1>Better Images</h1>

				<p><?php esc_html_e( 'Tired of resizing, compressing, converting, optimizing and exporting images over and over again? Better Images is a plugin that automagically does this hard work for you. Just upload your original image into the media library and the plugin will produce an image that is both better looking and smaller in size. And it will also resize the original full resolution image to save space.', 'better-images' ); ?></p>

			</div>

			<hr style="margin-top:1rem; margin-bottom:2rem;">

			<h2><?php esc_html_e( 'Settings', 'better-images' ); ?></h2>

			<p><?php esc_html_e( "Here's everything the plugin will do for you every time you upload an image.", 'better-images' ); ?></p>

			<table class="form-table" style="max-width: 1024px;">
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'Resize and compress the original full resolution image', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select name="resize_yesno" id="resize_yesno" disabled>
							<option value="no" <?php echo ( 'no' === $resizing_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'no', 'better-images' ); ?></option>
							<option value="yes" <?php echo ( 'yes' === $resizing_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'yes', 'better-images' ); ?></option>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( "Resizes and compresses the uploaded image to the maximum size of 2560 pixels. If the uploaded image is smaller than 2560 pixels it will be compressed but retain it's original size.", 'better-images' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'JPEG quality', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select id="quality" name="quality">
						<?php for ( $i = 1; $i <= 100; $i++ ) : ?>
							<option value="<?php echo $i; ?>" <?php if ( $compression_level === $i ) : ?> selected <?php endif; ?>><?php echo $i; ?></option>
						<?php endfor; ?>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( 'We have tweaked the plugin to give you the best balance between quality and file size. However, feel free to experiment with the best compression level for your specific need. Keep in mind that we use a higher compression level than the WordPress default value to compensate for the sharpening of the image.', 'better-images' ); ?></p>
						<p class="description"><?php esc_html_e( 'Recommended value: ', 'better-images' ); ?><code>62</code>
						<br><?php esc_html_e( 'WordPress default value: ', 'better-images' ); ?><code>82</code></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'Sharpen the image', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select name="sharpen_yesno" id="sharpen_yesno">
							<option value="no" <?php echo ( 'no' === $sharpen_image_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'no', 'better-images' ); ?></option>
							<option value="yes" <?php echo ( 'yes' === $sharpen_image_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'yes', 'better-images' ); ?></option>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( 'Sharpens the original image and all other size variants to make your image pop and look better. This will increase the file size by around 20%. To compensate for this we raise the compression level from 82% to 62%.', 'better-images' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'Remove EXIF data from image but keep color space profile', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select name="remove_exif_yesno" id="remove_exif_yesno">
							<option value="no" <?php echo ( 'no' === $remove_exif_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'no', 'better-images' ); ?></option>
							<option value="yes" <?php echo ( 'yes' === $remove_exif_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'yes', 'better-images' ); ?></option>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( 'EXIF data is information about the image embedded in the image file. This data is in most cases not used. Removing this data can shave off up to 30 kb per size variant. The color space profile will be retained so the upload image will look the same as on you computer.', 'better-images' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'Convert PNG image to JPEG', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select name="convert_png_yesno" id="convert_png_yesno">
							<option value="no" <?php echo ( 'no' === $convert_png_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'no', 'better-images' ); ?></option>
							<option value="yes" <?php echo ( 'yes' === $convert_png_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'yes', 'better-images' ); ?></option>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( 'A PNG image can be up to 20 times larger than the equivalent image in JPEG. Converting PNG to JPEG will not only save you a lot of disk space, but also make your website load much faster.', 'better-images' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'Convert image with CMYK color mode to RGB', 'better-images' ); ?></th>
					<td class="select-column" valign="top">
						<select name="convert_cmyk_yesno" id="convert_cmyk_yesno">
							<option value="no" <?php echo ( 'no' === $convert_cmyk_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'no', 'better-images' ); ?></option>
							<option value="yes" <?php echo ( 'yes' === $convert_cmyk_enabled ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'yes', 'better-images' ); ?></option>
						</select>
					</td>
					<td>
						<p class="description"><?php esc_html_e( 'CMYK color mode is used on images meant to be used in print. WordPress will not be able to compress the image so you will end upp with multiple variants of the image in different sizes, each of them weighing in at the same size in mega byte as the original full size image. Enable this feature to convert the image to RGB mode before any resizing or compression occurs.', 'better-images' ); ?></p>
					</td>
				</tr>
				<tr>
					<th class="title-column" scope="row"><?php esc_html_e( 'More things that Better Images does', 'better-images' ); ?></th>
					<td class="select-column" valign="top">

					</td>
					<td>
						<p class="description"><?php esc_html_e( 'Checks if the image already exist to avoid duplicates.', 'better-images' ); ?>
						<br><?php esc_html_e( 'Replaces special characters and non english letters in the filename.', 'better-images' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit" style="margin-top:10px;border-top:1px solid #eee;padding-top:20px;">
				<input type="hidden" name="action" value="update" />
				<?php wp_nonce_field( 'bi-options-update' ); ?>
				<input id="submit" name="bi-options-update" class="button button-primary" type="submit" value="<?php esc_html_e( 'Save Changes', 'better-images' ); ?>">
			</p>
		</form>
	</div>
	<?php
}


/**
 * Do image validations.
 *
 * - check if filename already exists (cancel upload if so)
 *
 * @param String $file Filename.
 */
function tp_validate_image( $file ) {
	tp_debug_log( 'Step 1: Validating uploaded image.' );

	$convert_png_enabled = ( get_option( 'bi_better_images_convert_png' ) === 'yes' ) ? true : false;

	$filename = tp_sanitize_file_name( $file['name'] );

	// If user uploads a PNG and conversion to JPG is enabled, check for an existng file with JPG extension.
	if ( file_is_png( $filename ) && $convert_png_enabled ) {
		tp_debug_log( 'Filetype is PNG. Image will be converted to JPG. Checkif if file with JPG extension exists.' );
		$filename = replace_extension( $filename, 'jpg', false );
	}

	if ( does_file_exists( $filename ) ) {
		$file['error'] = __( 'The file you are trying to upload already exists.', 'better-images' );
		tp_debug_log( 'File already exists, aborting upload.' );
	}

	return $file;
}

/**
 * Helper function to replace the file extension of
 * a file with another one.
 *
 * @param String  $filename Filename.
 * @param String  $new_extension The new extenstion.
 * @param Boolean $include_dir Include the path in the response.
 */
function replace_extension( $filename, $new_extension, $include_dir ) {
	$info = pathinfo( $filename );

	if ( $include_dir ) {
		return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '.' . $new_extension;
	}

	return $info['filename'] . '.' . $new_extension;
}

/**
 * Helper function to check if file is PNG.
 *
 * @param String $filename Filename.
 */
function file_is_png( $filename ) {
	$ext = pathinfo( $filename, PATHINFO_EXTENSION );
	return ( gettype( $ext ) === 'string' ) && ( strtoupper( $ext ) === 'PNG' );
}

/**
 * Check if a give file exists in the uploads folder or not.
 *
 * @param  String $filename The filename to check for.
 * @return Boolean If file exists, true, otherwise false.
 */
function does_file_exists( $filename ) {
	global $wpdb;

	return intval(
		$wpdb->get_var(
			"SELECT post_id FROM {$wpdb->postmeta} 
		WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%{$filename}'"
		)
	);
}

/**
 * Sanitize uploaded image.
 *
 * - sanitize filename (remove swedish letters etc)
 *
 * @param  String $filename The filename.
 */
function tp_sanitize_file_name( $filename ) {
	tp_debug_log( 'Step 2: Sanitizing filename of uploaded image.' );

	$sanitized_filename = remove_accents( $filename ); // Convert to ASCII.

	// Standard replacements.
	$invalid = array(
		' '   => '-',
		'%20' => '-',
		'_'   => '-',
	);

	$sanitized_filename = str_replace( array_keys( $invalid ), array_values( $invalid ), $sanitized_filename );
	$sanitized_filename = preg_replace( '/[^A-Za-z0-9-\. ]/', '', $sanitized_filename ); // Remove all non-alphanumeric except.
	$sanitized_filename = preg_replace( '/\.(?=.*\.)/', '', $sanitized_filename ); // Remove all but last.
	$sanitized_filename = preg_replace( '/-+/', '-', $sanitized_filename ); // Replace any more than one - in a row.
	$sanitized_filename = str_replace( '-.', '.', $sanitized_filename ); // Remove last - if at the end.
	$sanitized_filename = strtolower( $sanitized_filename ); // Lowercase.

	return $sanitized_filename;
}

/**
 * Check filetype of uploaded image and do conversions if neccessary.
 *
 * - convert from CMYK to RGB (if enabled).
 * - convert from PNG to JPG (if enabled).
 *
 * @param Object $image_data The image data.
 */
function tp_handle_uploaded( $image_data ) {
	tp_debug_log( 'Step 3: Check filetype of uploaded image.' );

	$convert_cmyk_enabled = ( get_option( 'bi_better_images_convert_cmyk' ) === 'yes' ) ? true : false;
	$convert_png_enabled  = ( get_option( 'bi_better_images_convert_png' ) === 'yes' ) ? true : false;

	if ( array_key_exists( 'type', $image_data ) && ( 'image/jpeg' !== $image_data['type'] && ( 'image/png' !== $image_data['type'] ) )
	) {
		tp_debug_log( 'Not a supported image format or no image, skipping. Type: ' . $image_data['type'] );
		return $image_data;
	}

	$image   = getimagesize( $image_data['file'] );
	$is_cmyk = array_key_exists( 'channels', $image ) && 4 === $image['channels'];

	$convert_png = file_is_png( $image_data['file'] ) && $convert_png_enabled;

	// Check if image is CMYK or if we should convert a PNG to JPG.
	if ( $is_cmyk || $convert_png ) {
		$image        = new Imagick( $image_data['file'] );
		$old_png_file = $image_data['file'];

		if ( $is_cmyk ) {

			if ( ! $convert_cmyk_enabled ) {
				tp_debug_log( 'Conversion to CMYK disabled, will not check for CMYK colorspace on image, proceeding.' );
				return $image_data;
			}

			tp_debug_log( 'Image is CMYK. Attempting to convert colorspace to RGB.' );
			$image = imagick_transform_cmyk_to_rgb( $image );
		}

		if ( $convert_png ) {
			tp_debug_log( 'Image is PNG and conversion to JPG is enabled. Attempting to convert to JPG.' );
			$image = imagick_convert_png_to_jpg( $image );

			$image_data['file'] = replace_extension( $image_data['file'], 'jpg', true );
			$image_data['url']  = replace_extension( $image_data['url'], 'jpg', true );
			$image_data['type'] = 'image/jpeg';
		}

		// Write the final image to disk.
		$image->writeImage( $image_data['file'] );

		// Remove the JPG from memory.
		$image->destroy();

		if ( $convert_png ) {
			tp_debug_log( 'Unlinking old PNG file: ' . $old_png_file );
			unlink( $old_png_file );
		}
	}

	return $image_data;
}

/**
 * Sharpen image and remove exif and metadata on upload.
 *
 * @param String $resized_file The filename of the resized file.
 */
function tp_sharpen_resized_files( $resized_file ) {
	tp_debug_log( 'Step 4: Sharpen image and remove exif and metadata on upload.' );

	$image = new Imagick( $resized_file );
	$size  = @getimagesize( $resized_file );

	if ( ! $size ) {
		return new WP_Error( 'invalid_image', __( 'Could not read image size.', 'better-images' ), $file );
	}

	list($orig_w, $orig_h, $orig_type) = $size;

	$remove_exif_enabled   = ( get_option( 'bi_better_images_remove_exif' ) === 'yes' ) ? true : false;
	$sharpen_image_enabled = ( get_option( 'bi_better_images_sharpen_image' ) === 'yes' ) ? true : false;
	$compression_level     = get_option( 'bi_better_images_quality' );

	if ( $sharpen_image_enabled ) {
		// We only want to use our sharpening on JPG files.
		switch ( $orig_type ) {
			case IMAGETYPE_JPEG:
				$image = imagick_sharpen_image( $image );
				break;
			default:
				break;
		}
	}

	if ( $remove_exif_enabled ) {
		// Strip the Exif data on the image (keep color profile).
		$image = imagick_strip_exif( $image );
	}

	// Compress the image.
	$image = imagick_compress_image( $image, $compression_level );

	// Write the final image to disk.
	$image->writeImage( $resized_file );

	// Remove the JPG from memory.
	$image->destroy();

	return $resized_file;
}

/**
 * Post processing of the uploaded image.
 *
 * - if neccessary, downsize the original image
 * - compress the uploaded image
 *
 * @param Object $image_data The image data.
 */
function tp_finialize_upload( $image_data ) {
	tp_debug_log( 'Step 5: Post processing of the uploaded image.' );

	if ( ! array_key_exists( 'file', $image_data ) ) {

		// If the media type is not an image we don't do this step.
		return $image_data;
	}

	$max_size              = get_option( 'bi_better_images_resize_threshold' ) === 0 ? 0 : get_option( 'bi_better_images_resize_threshold' );
	$resizing_enabled      = ( get_option( 'bi_better_images_resize_image' ) === 'yes' ) ? true : false;
	$remove_exif_enabled   = ( get_option( 'bi_better_images_remove_exif' ) === 'yes' ) ? true : false;
	$sharpen_image_enabled = ( get_option( 'bi_better_images_sharpen_image' ) === 'yes' ) ? true : false;
	$compression_level     = get_option( 'bi_better_images_quality' );

	// Find the path to the uploaded image.
	$upload_dir              = wp_upload_dir();
	$uploaded_image_location = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $image_data['file'];

	$image = new Imagick( $uploaded_image_location );
	$size  = @getimagesize( $uploaded_image_location );

	if ( ! $size ) {
		return new WP_Error( 'invalid_image', __( 'Could not read image size.', 'better-images' ), $file );
	}

	list($orig_w, $orig_h, $orig_type) = $size;

	if ( ( $orig_w > $max_size || $orig_h > $max_size ) && $resizing_enabled ) {
		$image->resizeImage( $max_size, $max_size, Imagick::FILTER_LANCZOS, 1, true );

		// set new image dimensions to wp.
		$image_data['width']  = $image->getImageWidth();
		$image_data['height'] = $image->getImageHeight();

		tp_debug_log( 'Image downsized. New image width: ' . $image->getImageWidth() . '. New image height: ' . $image->getImageHeight() . '.' );
	} else {
		tp_debug_log( 'No resizing of image was needed or re-sizing not enabled. Skipping.' );
	}

	if ( $sharpen_image_enabled ) {
		// We only want to use our sharpening on JPG files.
		switch ( $orig_type ) {
			case IMAGETYPE_JPEG:
				$image = imagick_sharpen_image( $image );
				break;
			default:
				break;
		}
	}

	if ( $remove_exif_enabled ) {
		// Strip the Exif data on the image (keep color profile).
		$image = imagick_strip_exif( $image );
	}

	// Compress the image.
	$image = imagick_compress_image( $image, $compression_level );

	// Write the final image to disk.
	$image->writeImage( $uploaded_image_location );

	// Remove the JPG from memory.
	$image->destroy();

	return $image_data;
}

/**
 * Make all image sizes available.
 *
 * @param Array $sizes The image sizes.
 */
function tp_display_image_size_names_muploader( $sizes ) {
	$new_sizes   = array();
	$added_sizes = get_intermediate_image_sizes();

	// $added_sizes is an indexed array, therefore need to convert it
	// to associative array, using $value for $key and $value.
	foreach ( $added_sizes as $key => $value ) {
		$new_sizes[ $value ] = $value;
	}

	// This preserves the labels in $sizes, and merges the two arrays.
	$new_sizes = array_merge( $new_sizes, $sizes );

	return $new_sizes;
}

/**
 * Debug logging function.
 *
 * @param String $message The debug message.
 */
function tp_debug_log( $message ) {
	global $debug_logger;

	if ( $debug_logger ) {
		error_log( print_r( $message, true ) );
	}
}
