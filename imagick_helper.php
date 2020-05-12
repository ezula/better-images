<?php

/**
 * Sharpen an image with ImageMagick.
 * 
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function imagick_sharpen_image($image, $compression_level) {

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
    $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
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
