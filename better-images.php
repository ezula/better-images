<?php
/*
Plugin Name: Better Images
Plugin URI: https://betterimages.se
Description: A wordpress plugin for better images.
Version: 0.0.2
Text Domain: better-images
Domain Path: /languages
Author: Webbson AB
Author URI: https://webbson.se
License: GPLv2
*/

$DEBUG_LOGGER = true;
$PLUGIN_VERSION = '0.0.2';

// Default plugin values
if (get_option('bi_better_images_version') != $PLUGIN_VERSION) {

    add_option('bi_better_images_version', $PLUGIN_VERSION, '','yes');
    add_option('bi_better_images_resize_threshold', '2560', '', 'yes');

    add_option('bi_better_images_quality', '75', '', 'yes');
    add_option('bi_better_images_resize_image', 'yes', '', 'yes');
    add_option('bi_better_images_sharpen_image', 'yes', '', 'yes');
    add_option('bi_better_images_remove_exif', 'yes', '', 'yes');
    add_option('bi_better_images_convert_png', 'yes', '', 'yes');
    add_option('bi_better_images_convert_cmyk', 'yes', '', 'yes');
}

// Hook in the options page
add_action('admin_menu', 'bi_better_images_options_page');

// Hook in all the filters and actions

add_filter('wp_handle_upload_prefilter', 'tp_validate_image');
add_filter('sanitize_file_name', 'tp_sanitize_file_name', 10, 1);
add_filter('big_image_size_threshold', 'tp_big_image_size_threshold');
add_filter('image_make_intermediate_size', 'tp_sharpen_resized_files', 900);
add_filter('wp_generate_attachment_metadata', 'tp_finialize_upload');

add_action('wp_handle_upload', 'tp_handle_uploaded');
add_action('plugins_loaded', 'better_images_load_plugin_textdomain');

/**
 * Load text domain failes.
 */
function better_images_load_plugin_textdomain() {
    load_plugin_textdomain( 'better-images', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

/**
 * We leave the threshold off when re-sizing is enabled,
 * otherwise we set it to default setting.
 */
function tp_big_image_size_threshold($threshold) {
    $resizing_enabled = (get_option('bi_better_images_resize_image') == 'yes') ? true : false;

    if ($resizing_enabled) {
        return false;
    }

    return 2560;
}

/**
* Add the options page.
*/
function bi_better_images_options_page() {
    global $bi_settings_page;
	if (function_exists('add_options_page')) {
      $bi_settings_page = add_options_page(
			'Better Images',
			'Better Images',
			'manage_options',
			'better-images',
			'bi_better_images_options');
	}
}

/**
* Define the options page for the plugin.
*/
function bi_better_images_options() {

    if (isset($_POST['bi-options-update'])) {
  
        if (!(current_user_can('manage_options')
            && wp_verify_nonce($_POST['_wpnonce'], 'bi-options-update'))) {

            wp_die("Not authorized");
        }
  
        $resizing_enabled = ($_POST['resize_yesno'] == 'yes' ? 'yes' : 'no');
        $sharpen_image_enabled = ($_POST['sharpen_yesno'] == 'yes' ? 'yes' : 'no');
        $remove_exif_enabled = ($_POST['remove_exif_yesno'] == 'yes' ? 'yes' : 'no');
        $convert_png_enabled = ($_POST['convert_png_yesno'] == 'yes' ? 'yes' : 'no');
        $convert_cmyk_enabled = ($_POST['convert_cmyk_yesno'] == 'yes' ? 'yes' : 'no');

        $compression_level = intval($_POST['quality']);
    
        $compression_level = ($compression_level == '') ? 1 : $compression_level;
        $compression_level = (ctype_digit(strval($compression_level)) == false) ? get_option('bi_better_images_quality') : $compression_level;
    
        if ($compression_level < 1) {
            $compression_level = 1;
        } else if ($compression_level > 100) {
            $compression_level = 100;
        }

        if ($resizing_enabled == 'yes') {
            update_option('bi_better_images_resize_image','yes');
        } else {
            update_option('bi_better_images_resize_image','no');
        }

        if ($sharpen_image_enabled == 'yes') {
            update_option('bi_better_images_sharpen_image','yes');
        } else {
            update_option('bi_better_images_sharpen_image','no');
        }

        if ($remove_exif_enabled == 'yes') {
            update_option('bi_better_images_remove_exif','yes');
        } else {
            update_option('bi_better_images_remove_exif','no');
        }

        if ($convert_png_enabled == 'yes') {
            update_option('bi_better_images_convert_png','yes');
        } else {
            update_option('bi_better_images_convert_png','no');
        }

        if ($convert_cmyk_enabled == 'yes') {
            update_option('bi_better_images_convert_cmyk','yes');
        } else {
            update_option('bi_better_images_convert_cmyk','no');
        }
    
        update_option('bi_better_images_quality', $compression_level);
        echo('<div id="message" class="updated fade"><p><strong>Options have been updated.</strong></p></div>');
    }
  
    $compression_level = intval(get_option('bi_better_images_quality'));
    $resizing_enabled = get_option('bi_better_images_resize_image');
    $sharpen_image_enabled = get_option('bi_better_images_sharpen_image');
    $remove_exif_enabled = get_option('bi_better_images_remove_exif');
    $convert_png_enabled = get_option('bi_better_images_convert_png');
    $convert_cmyk_enabled = get_option('bi_better_images_convert_cmyk');
  ?>

  
  <div class="wrap">
        <form method="post" accept-charset="utf-8">

            <h1>Better Images</h1>

            <hr style="margin-top:20px; margin-bottom:5px;">
            <hr style="margin-top:5px; margin-bottom:30px;">

            <h3><?php _e('Re-sizing options', 'better-images'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable re-sizing of large images', 'better-images'); ?></th>
                    <td valign="top">
                        <select name="resize_yesno" id="resize_yesno">
                            <option value="no" label="no" <?php echo ($resizing_enabled == 'no') ? 'selected="selected"' : ''; ?>>
                                <?php _e('NO', 'better-images'); ?>
                            </option>
                            <option value="yes" label="yes" <?php echo ($resizing_enabled == 'yes') ? 'selected="selected"' : ''; ?>>
                                <?php _e('YES', 'better-images'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Shapen re-sized images', 'better-images'); ?></th>
                    <td valign="top">
                        <select name="sharpen_yesno" id="sharpen_yesno">
                            <option value="no" label="no" <?php echo ($sharpen_image_enabled == 'no') ? 'selected="selected"' : ''; ?>>
                                <?php _e('NO', 'better-images'); ?>
                            </option>
                            <option value="yes" label="yes" <?php echo ($sharpen_image_enabled == 'yes') ? 'selected="selected"' : ''; ?>>
                                <?php _e('YES', 'better-images'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Remove EXIF-information from image', 'better-images'); ?></th>
                    <td valign="top">
                        <select name="remove_exif_yesno" id="remove_exif_yesno">
                            <option value="no" label="no" <?php echo ($remove_exif_enabled == 'no') ? 'selected="selected"' : ''; ?>>
                                <?php _e('NO', 'better-images'); ?>
                            </option>
                            <option value="yes" label="yes" <?php echo ($remove_exif_enabled == 'yes') ? 'selected="selected"' : ''; ?>>
                                <?php _e('YES', 'better-images'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Convert PNG images to JPG', 'better-images'); ?></th>
                    <td valign="top">
                        <select name="convert_png_yesno" id="convert_png_yesno">
                            <option value="no" label="no" <?php echo ($convert_png_enabled == 'no') ? 'selected="selected"' : ''; ?>>
                                <?php _e('NO', 'better-images'); ?>
                            </option>
                            <option value="yes" label="yes" <?php echo ($convert_png_enabled == 'yes') ? 'selected="selected"' : ''; ?>>
                                <?php _e('YES', 'better-images'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Convert CMYK colorspace to RGB', 'better-images'); ?></th>
                    <td valign="top">
                        <select name="convert_cmyk_yesno" id="convert_cmyk_yesno">
                            <option value="no" label="no" <?php echo ($convert_cmyk_enabled == 'no') ? 'selected="selected"' : ''; ?>>
                                <?php _e('NO', 'better-images'); ?>
                            </option>
                            <option value="yes" label="yes" <?php echo ($convert_cmyk_enabled == 'yes') ? 'selected="selected"' : ''; ?>>
                                <?php _e('YES', 'better-images'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
    
            <hr style="margin-top:20px; margin-bottom:30px;">
    
            <h3><?php _e('Compression options', 'better-images'); ?></h3>
            <p style="max-width:700px"><?php _e('The following settings will only apply to uploaded JPEG images and images converted to JPEG format.', 'better-images'); ?></p>
    
            <table class="form-table">
  
                <tr>
                    <th scope="row"><?php _e('JPEG compression level', 'better-images'); ?></th>
                    <td valign="top">
                        <select id="quality" name="quality">
                        <?php for($i=1; $i<=100; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php if($compression_level == $i) : ?>selected<?php endif; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                        </select>
                        <p class="description"><code>1</code><?php _e(' = low quality (smallest files)', 'better-images'); ?>
                        <br><code>100</code><?php _e(' = best quality (largest files)', 'better-images'); ?>
                        <br><?php _e('Recommended value: ', 'better-images'); ?><code>75</code></p>
                    </td>
                </tr>
  
            </table>
  
  
            <p class="submit" style="margin-top:10px;border-top:1px solid #eee;padding-top:20px;">
                <input type="hidden" name="action" value="update" />
                <?php wp_nonce_field('bi-options-update'); ?>
                <input id="submit" name="bi-options-update" class="button button-primary" type="submit" value="<?php _e('Update Options', 'better-images'); ?>">
            </p>
      </form>
  
  </div>
  <?php
}


/**
 * Do image validations.
 * 
 * - check if filename already exists (cancel upload if so)
 */
function tp_validate_image($file) {

    tp_debug_log("Step 1: Validating uploaded image.");

    $convert_png_enabled = (get_option('bi_better_images_convert_png') == 'yes') ? true : false;

    $filename = tp_sanitize_file_name($file['name']);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    // If user uploads a PNG and conversion to JPG is enabled, check for an existng file with JPG extension.
    if ((gettype($ext) == 'string') && (strtoupper($ext) == 'PNG') && $convert_png_enabled) {
        tp_debug_log("Filetype is PNG. Image will be converted to JPG. Checkif if file with JPG extension exists.");
        $filename = replace_extension($filename, 'jpg');
    }

    if (does_file_exists($filename)) {
        $file['error'] = __('The file you are trying to upload already exists.', 'better-images');
        tp_debug_log("File already exists, aborting upload.");
    }

    return $file;
}

/**
 * Helper function to replace the file extension of
 * a file with another one.
 */
function replace_extension($filename, $new_extension) {
    $info = pathinfo($filename);
    return $info['filename'] . '.' . $new_extension;
}

/**
 * Check if a give file exists in the uploads folder or not.
 * 
 * @param String $filename The filename to check for.
 * @return Boolean If file exists, true, otherwise false.
 */
function does_file_exists($filename) {
    global $wpdb;

    return intval( $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename'" ) );
  }

/**
 * Sanitize uploaded image.
 * 
 * - sanitize filename (remove swedish letters etc)
 */
function tp_sanitize_file_name($filename) {

    tp_debug_log("Step 2: Sanitizing filename of uploaded image.");

    $sanitized_filename = remove_accents($filename); // Convert to ASCII

    // Standard replacements
    $invalid = array(
        ' ' => '-',
        '%20' => '-',
        '_' => '-',
    );

    $sanitized_filename = str_replace(array_keys($invalid), array_values($invalid), $sanitized_filename);
    $sanitized_filename = preg_replace('/[^A-Za-z0-9-\. ]/', '', $sanitized_filename); // Remove all non-alphanumeric except .
    $sanitized_filename = preg_replace('/\.(?=.*\.)/', '', $sanitized_filename); // Remove all but last .
    $sanitized_filename = preg_replace('/-+/', '-', $sanitized_filename); // Replace any more than one - in a row
    $sanitized_filename = str_replace('-.', '.', $sanitized_filename); // Remove last - if at the end
    $sanitized_filename = strtolower($sanitized_filename); // Lowercase

    return $sanitized_filename;
}

/**
 * Check filetype of uploaded image and do conversions if neccessary.
 * 
 * - convert from CMYK to RGB.
 */
function tp_handle_uploaded($image_data) {

    tp_debug_log("Step 3: Check filetype of uploaded image.");

    $convert_cmyk_enabled = (get_option('bi_better_images_convert_cmyk') == 'yes') ? true : false;

    if (array_key_exists('type', $image_data) && ($image_data['type'] !=
        'image/jpeg' && $image_data['type'] != 'image/png')) {
            tp_debug_log("Not a supported image format or no image, skipping. Type: " . $image_data['type']);
            return $image_data;
    }

    if (!$convert_cmyk_enabled) {
        tp_debug_log("Conversion to CMYK disabled, will not check for CMYK colorspace on image, proceeding.");
        return $image_data; 
    }

    $image = getimagesize($image_data['file']);

    // Check if image is CMYK and attempt to convert to RGB.
    if (array_key_exists('channels', $image) && $image['channels'] === 4) {
        tp_debug_log("Image is CMYK, attempting to convert to RGB.");

        $image = new Imagick($image_data['file']);
        $image = imagick_transform_cmyk_to_rgb($image);
        
        // Write the final image to disk.
        $image->writeImage($image_data['file']);

	    // Remove the JPG from memory
	    $image->destroy();
    }

    return $image_data;
}

/**
 * Sharpen image and remove exif and metadata on upload.
 */
function tp_sharpen_resized_files($resized_file) {
	
    tp_debug_log("Step 4: Sharpen image and remove exif and metadata on upload.");

	$image = new Imagick($resized_file);
    $size = @getimagesize($resized_file);
    
	if (!$size) {
        return new WP_Error('invalid_image', __('Could not read image size.', 'better-images'), $file);
    }
    
    list($orig_w,$orig_h,$orig_type) = $size;

    $max_width  = get_option('bi_better_images_width')==0 ? false : get_option('bi_better_images_width');
    $max_height = get_option('bi_better_images_height')==0 ? false : get_option('bi_better_images_height');
    $remove_exif_enabled = (get_option('bi_better_images_remove_exif') == 'yes') ? true : false;
    $sharpen_image_enabled = (get_option('bi_better_images_sharpen_image') == 'yes') ? true : false;

    if ($sharpen_image_enabled) {
        // We only want to use our sharpening on JPG files
        switch($orig_type) {
            case IMAGETYPE_JPEG:
            
                $image = imagick_sharpen_image($image);
                break;
            default:
                break;
        }
    }

    if ($remove_exif_enabled) {
        // Strip the Exif data on the image (keep color profile)
        $image = imagick_strip_exif($image);
    }

    // Write the final image to disk.
    $image->writeImage($resized_file);

	// Remove the JPG from memory
	$image->destroy();
	
	return $resized_file;
}

/**
 * Post processing of the uploaded image.
 * 
 * - if neccessary, downsize the original image
 * - compress the uploaded image
 */
function tp_finialize_upload($image_data) {

    tp_debug_log("Step 5: Post processing of the uploaded image.");

    if (!array_key_exists('file', $image_data)) {

        // If the media type is not an image we don't do this step.
        return $image_data;
    }

    $max_size = get_option('bi_better_images_resize_threshold') == 0 ? 0 : get_option('bi_better_images_resize_threshold');
    $resizing_enabled = (get_option('bi_better_images_resize_image') == 'yes') ? true : false;
    $remove_exif_enabled = (get_option('bi_better_images_remove_exif') == 'yes') ? true : false;
    $sharpen_image_enabled = (get_option('bi_better_images_sharpen_image') == 'yes') ? true : false;

    // Find the path to the uploaded image.
    $upload_dir = wp_upload_dir();
    $uploaded_image_location = $upload_dir['basedir'] . DIRECTORY_SEPARATOR .$image_data['file'];

    $image = new Imagick($uploaded_image_location); 
    $size = @getimagesize($uploaded_image_location);

    if (!$size) {
        return new WP_Error('invalid_image', __('Could not read image size.', 'better-images'), $file);
    }

    list($orig_w,$orig_h,$orig_type) = $size;

    if (($orig_w > $max_size || $orig_h > $max_size) && $resizing_enabled) {
        $image->resizeImage($max_size, $max_size, Imagick::FILTER_LANCZOS, 1, true);

        // set new image dimensions to wp
        $image_data['width'] = $image->getImageWidth();
        $image_data['height'] = $image->getImageHeight();

        tp_debug_log('Image downsized. New image width: ' . $image->getImageWidth() . '. New image height: ' . $image->getImageHeight() . '.');
    } else {
        tp_debug_log("No resizing of image was needed or re-sizing not enabled. Skipping.");
    }

    if ($sharpen_image_enabled) {
        // We only want to use our sharpening on JPG files
        switch($orig_type) {
            case IMAGETYPE_JPEG:
                $image = imagick_sharpen_image($image);
                break;
            default:
                break;
        }
    }

    if ($remove_exif_enabled) {
        // Strip the Exif data on the image (keep color profile)
        $image = imagick_strip_exif($image);
    }

    // Write the final image to disk.
    $image->writeImage($uploaded_image_location);

    // Remove the JPG from memory
    $image->destroy();

    return $image_data;
}

/**
* Debug logging function.
*/
function tp_debug_log($message) {
    global $DEBUG_LOGGER;

    if ($DEBUG_LOGGER) {
        error_log(print_r($message, true));
    }
}

/**
 * Sharpen an image with ImageMagick.
 * 
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function imagick_sharpen_image($image) {

    $compression_level = get_option('bi_better_images_quality');

    // Sharpen the image (the default is via the Lanczos algorithm)
    $image->unsharpMaskImage(0, 0.6, 1.4, 0);

    // Store the JPG file with the compression level specified by the user (or default).
    $image->setImageFormat("jpg");
    $image->setImageCompression(Imagick::COMPRESSION_JPEG);
    $image->setImageCompressionQuality($compression_level);
    
    tp_debug_log("Image has been sharpened.");
    return $image;
}

/**
 * Strip the Exif data of an image with ImageMagic
 * but keep the color profile.
 * 
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function imagick_strip_exif($image) {

    // Strip Exif data but keep the color profile.
    $profiles = $image->getImageProfiles("icc", true);
    $has_icc_profile = (array_key_exists('icc', $profiles) !== false);
    $image->stripImage();

    if ($has_icc_profile) {
        $image->profileImage("icc", $profiles['icc']);
    } else {
        tp_debug_log("Warning: No color profile found on image.");
    }

    tp_debug_log("Image has been stripped of exif information.");
    return $image;
}

/**
 * Convert a PNG file to a JPG file with ImageMagick.
 * 
 * @param Imagick $image The image file.
 * @return Imagick The converted image.
 */
function imagick_convert_png_to_jpg($image) {
    $image->setImageBackgroundColor('white');
    $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
    $image->setImageFormat('jpg');

    return $image;
}

/**
 * Transform the image colorspace from CMYK to RGB.
 * 
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function imagick_transform_cmyk_to_rgb($image) {
    $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
    return $image;
}
