<?php
/**
 * Helper class for better-images plugin to handle image operations.
 *
 * @package better-images
 */

/**
 * Sharpen an image with ImageMagick.
 *
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function wnbi_imagick_sharpen_image( $image ) {

	// Sharpen the image (the default is via the Lanczos algorithm).
	$image->unsharpMaskImage( 0, 0.4, 1.2, 0.01 );

	wnbi_debug_log( 'Image has been sharpened.' );
	return $image;
}

/**
 * Compress an image with ImageMagick.
 *
 * @param Imagick $image The imagick image.
 * @param Number  $compression_level The compression level.
 * @return Imagick The sharpened image.
 */
function wnbi_imagick_compress_image( $image, $compression_level ) {

	// Store the JPG file with the compression level specified by the user (or default).
	$image->setImageFormat( 'jpg' );
	$image->setImageCompression( Imagick::COMPRESSION_JPEG );
	$image->setImageCompressionQuality( $compression_level );

	return $image;
}

/**
 * Strip the Exif data of an image with ImageMagic
 * but keep the color profile.
 *
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function wnbi_imagick_strip_exif( $image ) {

	// Strip Exif data but keep the color profile.
	$profiles        = $image->getImageProfiles( 'icc', true );
	$has_icc_profile = ( array_key_exists( 'icc', $profiles ) !== false );
	$image->stripImage();

	if ( $has_icc_profile ) {
		$image->profileImage( 'icc', $profiles['icc'] );
	} else {
		wnbi_debug_log( 'Warning: No color profile found on image.' );
		// We may want to add a color profile in the upcoming releases.
		// $icc_rgb = file_get_contents( plugin_dir_path( __FILE__ ) . 'sRGB-IEC61966-2.1.icc' );
		// $image->profileImage( 'icc', $icc_rgb );
	}

	wnbi_debug_log( 'Image has been stripped of exif information.' );
	return $image;
}

/**
 * Convert a PNG file to a JPG file with ImageMagick.
 *
 * @param Imagick $image The image file.
 * @return Imagick The converted image.
 */
function wnbi_imagick_convert_png_to_jpg( $image ) {
	$image->setImageBackgroundColor( 'white' );
	$image = $image->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
	$image->setImageFormat( 'jpg' );

	return $image;
}

/**
 * Transform the image colorspace from CMYK to RGB.
 *
 * @param Imagick $image The imagick image.
 * @return Imagick The sharpened image.
 */
function wnbi_imagick_transform_cmyk_to_rgb( $image ) {

	$profiles        = $image->getImageProfiles( '*', false );
	$has_icc_profile = ( array_search( 'icc', $profiles ) !== false );

	if ( false === $has_icc_profile ) {
		$icc_cmyk = file_get_contents( plugin_dir_path( __FILE__ ) . 'USWebUncoated.icc' );
		$image->profileImage( 'icc', $icc_cmyk );
		unset( $icc_cmyk );
	}

	$icc_rgb = file_get_contents( plugin_dir_path( __FILE__ ) . 'sRGB-IEC61966-2.1.icc' );
	$image->profileImage( 'icc', $icc_rgb );

	return $image;
}
