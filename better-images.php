<?php
/*
Plugin Name: Better Images
Plugin URI: https://betterimages.se
Description: A wordpress plugin for better images.
Version: 0.0.2
Author: Webbson AB
Author URI: https://webbson.se
License: GPLv2
*/

$DEBUG_LOGGER = true;
$PLUGIN_VERSION = '0.0.2';

// Default plugin values
if (get_option('bi_better_images_version') != $PLUGIN_VERSION) {

    add_option('bi_better_images_version', 			$PLUGIN_VERSION, '','yes');
    add_option('bi_better_images_width', 				'2560', '', 'yes');
    add_option('bi_better_images_height',				'2560', '', 'yes');
    add_option('bi_better_images_quality',				'75', '', 'yes');
}

// Hook in the options page
add_action('admin_menu', 'bi_better_images_options_page');

// Hook in all the filters and actions

add_filter('wp_handle_upload_prefilter', 'tp_validate_image');
add_filter('sanitize_file_name', 'tp_sanitize_file_name', 10, 1);
add_filter( 'big_image_size_threshold', '__return_false' );
add_filter('image_make_intermediate_size', 'tp_sharpen_resized_files', 900);
add_filter('wp_generate_attachment_metadata', 'tp_finialize_upload');

add_action('wp_handle_upload', 'tp_resize_uploaded');

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
  
        $max_width = intval($_POST['maxwidth']);
        $max_height = intval($_POST['maxheight']);
        $compression_level = intval($_POST['quality']);
  
        $max_width = ($max_width == '') ? 0 : $max_width;
        $max_width = (ctype_digit(strval($max_width)) == false) ? get_option('bi_better_images_width') : $max_width;
        update_option('bi_better_images_width', $max_width);
    
        $max_height = ($max_height == '') ? 0 : $max_height;
        $max_height = (ctype_digit(strval($max_height)) == false) ? get_option('bi_better_images_height') : $max_height;
        update_option('bi_better_images_height', $max_height);
    
        $compression_level = ($compression_level == '') ? 1 : $compression_level;
        $compression_level = (ctype_digit(strval($compression_level)) == false) ? get_option('bi_better_images_quality') : $compression_level;
    
        if ($compression_level < 1) {
            $compression_level = 1;
        } else if ($compression_level > 100) {
            $compression_level = 100;
        }
    
        update_option('bi_better_images_quality', $compression_level);
        echo('<div id="message" class="updated fade"><p><strong>Options have been updated.</strong></p></div>');
    }
  
    $compression_level = intval(get_option('bi_better_images_quality'));
    $max_width = get_option('bi_better_images_width');
    $max_height = get_option('bi_better_images_height');

  ?>

  
  <div class="wrap">
      <form method="post" accept-charset="utf-8">
  
          <h1>Better Images</h1>
  
          <hr style="margin-top:20px; margin-bottom:5;">
          <hr style="margin-top:5px; margin-bottom:30px;">
  
          <h3>Re-sizing options</h3>
          <table class="form-table">
  
              <tr>
                  <th scope="row">Max image dimensions</th>
  
                  <td>
                      <fieldset>
                        <legend class="screen-reader-text">
                            <span>Maximum width and height</span>
                        </legend>
                        <label for="maxwidth">Max width</label>
                        <input name="maxwidth" step="1" min="0" id="maxwidth" class="small-text" type="number" value="<?php echo $max_width; ?>">
                        &nbsp;&nbsp;&nbsp;
                        <label for="maxheight">Max height</label>
                        <input name="maxheight" step="1" min="0" id="maxheight" class="small-text" type="number" value="<?php echo $max_height; ?>">
                        <p class="description">
                            Set to zero or a very high value to prevent resizing in that dimension.
                            <br />Recommended values: <code>2560x2560</code>
                        </p>
                      </fieldset>
                  </td>
  
  
              </tr>
  
          </table>
  
          <hr style="margin-top:20px; margin-bottom:30px;">
  
          <h3>Compression options</h3>
          <p style="max-width:700px">The following settings will only apply to uploaded JPEG images and images converted to JPEG format.</p>
  
          <table class="form-table">
  
              <tr>
                  <th scope="row">JPEG compression level</th>
                  <td valign="top">
                      <select id="quality" name="quality">
                      <?php for($i=1; $i<=100; $i++) : ?>
                          <option value="<?php echo $i; ?>" <?php if($compression_level == $i) : ?>selected<?php endif; ?>><?php echo $i; ?></option>
                      <?php endfor; ?>
                      </select>
                      <p class="description"><code>1</code> = low quality (smallest files)
                      <br><code>100</code> = best quality (largest files)
                      <br>Recommended value: <code>75</code></p>
                  </td>
              </tr>
  
          </table>
  
  
          <p class="submit" style="margin-top:10px;border-top:1px solid #eee;padding-top:20px;">
            <input type="hidden" name="action" value="update" />
            <?php wp_nonce_field('bi-options-update'); ?>
            <input id="submit" name="bi-options-update" class="button button-primary" type="submit" value="Update Options">
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

    $upload_dir = wp_upload_dir();
    $filename = tp_sanitize_file_name($file['name']);

    if (does_file_exists($filename)) {
        $file['error'] = "The file you are trying to upload already exists.";
        tp_debug_log("File already exists, aborting upload.");
    }

    return $file;
}

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
function tp_resize_uploaded($image_data) {

    tp_debug_log("Step 3: Check filetype of uploaded image.");

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
        return new WP_Error('invalid_image', __('Could not read image size.'), $file);
    }
    
    list($orig_w,$orig_h,$orig_type) = $size;

    $max_width  = get_option('bi_better_images_width')==0 ? false : get_option('bi_better_images_width');
    $max_height = get_option('bi_better_images_height')==0 ? false : get_option('bi_better_images_height');

	// We only want to use our sharpening on JPG files
    switch($orig_type) {
        case IMAGETYPE_JPEG:
        
            $image = imagick_sharpen_image($image);
            break;
        default:
            break;
    }

    // Strip the Exif data on the image (keep color profile)
    $image = imagick_strip_exif($image);

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

    // Find the path to the uploaded image.
    $upload_dir = wp_upload_dir();
    $uploaded_image_location = $upload_dir['basedir'] . DIRECTORY_SEPARATOR .$image_data['file'];

    $image = new Imagick($uploaded_image_location); 
    $size = @getimagesize($uploaded_image_location);

    if (!$size) {
        return new WP_Error('invalid_image', __('Could not read image size.'), $file);
    }

    list($orig_w,$orig_h,$orig_type) = $size;

    $max_width  = get_option('bi_better_images_width')==0 ? false : get_option('bi_better_images_width');
    $max_height = get_option('bi_better_images_height')==0 ? false : get_option('bi_better_images_height');

    if ($orig_w > $max_width || $orig_h > $max_height) {
        $image->resizeImage($max_width, $max_height, Imagick::FILTER_LANCZOS, 1, true);

        // set new image dimensions to wp
        $image_data['width'] = $image->getImageWidth();
        $image_data['height'] = $image->getImageHeight();

        tp_debug_log('Image downsized. New image width: ' . $image->getImageWidth() . '. New image height: ' . $image->getImageHeight() . '.');
    } else {
        tp_debug_log("No resizing of image was needed. Skipping.");
    }

    // We only want to use our sharpening on JPG files
    switch($orig_type) {
        case IMAGETYPE_JPEG:
            $image = imagick_sharpen_image($image);
            break;
        default:
            break;
    }

    // Strip the Exif data on the image (keep color profile)
    $image = imagick_strip_exif($image);

    // Write the final image to disk.
    $image->writeImage($uploaded_image_location);

    // Remove the JPG from memory
    $image->destroy();

    tp_debug_log($image_data);

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
