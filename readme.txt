=== Better Images - Sharpen, compress, optimize and resize original full image ===
Contributors: hemenderki, svunz, charliederki
Tags: images, image, sharpen, sharpening, compress, compression, optimize, optimization, resize, resizing, original image, full image
Requires at least: 5.3
Tested up to: 5.4.1
Requires PHP: 5.6
License: GPLv2

Tired of resizing, compressing, converting, optimizing and exporting images? This plugin will automagically do this hard work for you.

== Description ==
NOTE: Better Images only works with ImageMagick, not with GD. Install this plugin to check if you have ImageMagick installed.

Tired of resizing, compressing, converting, optimizing and exporting images over and over again? Better Images is a plugin that automagically does this hard work for you. Just drag and drop your image into the media library and the plugin will produce an image that is both better looking and smaller in size. And it will also resize the original full resolution image to save space.

## Here\'s everything that Better Images will do for you every time you upload an image:

- Checks if the image already exist to avoid duplicates
- Replaces special characters and non english letters in the filename
- Removes EXIF data but keeps color space profile
- Converts from PNG to JPG
- Converts from CMYK to RGB
- Sharpens the image to make it more appealing and crisp
- Resizes and compresses the original full resolution image
- Displays all image sizes in the editing screen

## And here\'s the nitty-gritty of what is actually happening in the background:

### Checks if the image already exist to avoid duplicates

One of the things that will take unnecessary space from your server is uploading the same image multiple times. Often times we forget that we already uploaded the image once before, so we upload it again. WordPress by default won\'t warn you that you are about to upload a duplicate image. Instead it puts a number in the filename and lets the image pass.

With Better Images every time you upload a file it will search the database to see if the image already exist and give you a message to make you aware of that and help you avoid uploading duplicates of the same image.

### Replaces speci**al characters and non** english letters in the filename

WordPress will allow you to upload images with special characters and non english letters in the filename. Everything will work properly. Until that is you decide to move your website or restore it from a backup. Suddenly all image links with special characters and non english letters will break and you will have to reassign these images in all pages and posts manually.

To protect you from that Better Images will clean up and replace all special characters and non english letters in the filename for you.

### Removes EXIF data but keeps color space profile

Almost all images taken with a camera or downloaded from a stock photo library has EXIF data embedded in them. EXIF data is information about shutter speed, exposure compensation, aperture speed, what metering system, if a flash was used, ISO number, date and time the image was taken, whitebalance, GPS coordinates and so on. This data is in the majority of time not used or needed on a website. Therefore we remove the EXIF data and shave off up to 30 kb from the file size.

Removing the EXIF data however also removes the color space profile which will result in an image that looks different form the original. Since we wouldn\'t want that we leave the color space profile untouched.

### Converts from PNG to JPG

The file format PNG (Portable Network Graphics) is a popular image format because of its non destructive nature. However it is not at all suitable for photographs that will be used on a website due to its heavy size. In contrast a JPG image can be up to 20 times lighter in file size.

Better Images can convert uploaded PNG image to JPG for you. This will not only save you a lot of disk space, but also make your website load much faster.

### Converts from CMYK to RGB

Sometimes you will get an image that\'s exported to be used in print and therefore it uses the CMYK color mode. WordPress is not that great at handling this so it will create multiple variants of the image in different sizes, each of them weighing in at the same size in mega byte as the original full size image. That\'s crazy!

Better Images solves this by converting the image to RGB mode before any resizing or compression occurs.

### Sharpens the image to make it more appealing and crisp

Every time we resize an image it becomes blurry. Apps like Photoshop, Pixelmator, Affinity Photo and the like will compensate this by applying som degree of sharpening to make images look better. WordPress does not do that and that\'s why we end up with blurry images.

However Better Images does that for you. We have tweaked the sharpening level to avoid over sharpening artifacts. The plugin applies the perfect amount of sharpening to make your images pop and look at their best.

### Resizes and compresses the original full resolution image

In version 5.3 WordPress introduced handling of big images. The solution that was implemented is to create a new size variant of 2560 pixels with the addition of the word \"scaled\" in the filename. This variant serves as the new full size image on the website. The real original full size image though is not deleted but kept on the server in case one would want to resize to a different size or regenerate thumbnails. This image is never used on the website however and resizing images to pixel perfect sizes is a thing of the past due to the advent of responsive image handling.

What Better Images does is resizing the original full size image to the maximum size of 2560 pixels replacing the default scaled variant. In the process we also get rid of the word \"scaled\" in the filename.

### Displays all image sizes in the editing screen

By default WordPress will only display and let you choose from four sizes in the editing screen.
- Thumbnail (max 150px square)
- Medium (max 300px width and height)
- Large (max 1024px width and height)
- Full (full/original image)

WordPress however creates four more sizes but does not display them.
- Max 768px width
- Max 1536px width and height
- Max 2048px width and height
- Max 2560px width and height

Better Images will display and let you choose from all these sizes. This is also compatible with many page builders such as Oxygen, Beaver Builder, Divi, Elementor, Brizy and many more.

## You are in control

All features are enabled by default and most of them can be disabled when needed. Let say for example you are doing a downloads page with press images and want to have full sized images in CMYK mode. No problem, just turn off the setting for CMYK conversion and full resolution image resizing.

== Installation ==
1. Visit the plugins page within your dashboard and select \"Add New\".
2. Search for \"Better Images\"
3. Activate Better Images from your plugins page.

== Frequently Asked Questions ==
= Should I compress and resize my images before uploading? =

No, Better Images will do that for you. In fact uploading a compressed and resized image will make the final result look worse and in some cases the file size will be larger. You should always upload the original uncompressed full size image and let Better Images do the rest.

= Does this plugin work with GD? =

No, Better Images does not work with GD. For the plugin to work you will need ImageMagick installed on the server.

= How can I check if I have ImageMagick installed on my server? =

You can find that out by installing this plugin and go to Settings > Better Images. If you see an error massage and no settings then you do not have ImageMagic.

= What if I don\'t have GD on my server? =

Then we would recommend contacting your hosting company and ask them to activate ImageMagick for you.

= Does Better Images support responsive images =

Yes. Better Images utilizes the built in functionality in WordPress for responsive images. That means it will create multiple size variant and display the most appropriate one on your website.

= What image size variants is created? =

WordPress automatically creates the following size variants by default:
- 150x150 pixels
- 300x300 pixels
- 768 pixels
- 1024x1024 pixels
- 1536x1536 pixels
- 2048x2048 pixels
- 2560x2560 pixels

= Do you have any recommendations concerning images sizes? =

To spread the sizes more evenly we recommend changing 150x150 to 256x256, and 300x300 to 512x512.

= What happens to the full size original image? =

The full size original image is resized  to a maximum of 2560 pixels. If the uploaded image is smaller than 2560 pixels it will be compressed but retain its original size. The image will also get all the improvements of the enabled settings.

= Can I regenerate images with Better Images turned on? =

Yes, that is possible. However, the images will be regenerated from the compressed 2560 pixel image, not the original full size image since that images does not exist anymore.

= Can I edit and resize the image i I use Better Images? =

Yes that is possible, but, Better Images is meant to make image editing a thing of the past. Since WordPress is using responsive image handling there are no reasons to manually resize images. Also keep in mind that the resizing will happen from an already compressed and resized image.

== Changelog ==
### 0.8.2

Beta release.
