<?php
/**
 * Helper class for better-images plugin to handle image operations with GD.
 *
 * @package better-images
 */

/**
 * Sharpen an image with GD.
 *
 * @param GdImage $image The GD image.
 * @return GdImage The sharpened image.
 */
function wnbi_gd_sharpen_image( $image ) {
	$matrix = array(
		array( -1, -1, -1 ),
		array( -1, 17, -1 ),
		array( -1, -1, -1 ),
	);

	$divisor = array_sum( array_map( 'array_sum', $matrix ) );
	$offset  = 0;
	imageconvolution( $image, $matrix, $divisor, $offset );

	return $image;
}

/**
 * Resize a GD image and keep aspect ratio.
 *
 * @param GdImage $image    The GD image.
 * @param String   $filename The path to the image file.
 * @param Number   $max_w    Max width of image.
 * @param Number   $max_h    Mac height of the image.
 * @return resource The resized image.
 */
function wnbi_gd_resize_image( $image, $filename, $max_w, $max_h ) {
	$dims = image_resize_dimensions( imagesx( $image ), imagesy( $image ), $max_w, $max_h, false );

	if ( ! $dims ) {
		return new WP_Error( 'error_getting_dimensions', __( 'Could not calculate resized image dimensions' ), $filename );
	}

	list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

	$resized = wp_imagecreatetruecolor( $dst_w, $dst_h );
	imagecopyresampled( $resized, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

	if ( is_resource( $resized ) ) {
		return $resized;
	}

	return new WP_Error( 'image_resize_error', __( 'Image resize failed.' ), $filename );
}

/**
 * Convert a PNG image to JPG with GD.
 *
 * @param GdImage $png_image The GD image.
 * @return GdImage The JPG image.
 */
function wnbi_gd_convert_png_to_jpg( $png_image ) {
	$bg = imagecreatetruecolor( imagesx( $png_image ), imagesy( $png_image ) );
	imagefill( $bg, 0, 0, imagecolorallocate( $bg, 255, 255, 255 ) );
	imagealphablending( $bg, 1 );
	imagecopy( $bg, $png_image, 0, 0, 0, 0, imagesx( $png_image ), imagesy( $png_image ) );

	return $bg;
}
