# better-images

![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/better-images)

A WordPress plugin for [Better Images](https://wordpress.org/plugins/better-images/).

Tired of resizing, compressing, converting, optimizing and exporting images? This plugin will automagically do this hard work for you.

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

## How to generate/update .pot file:

From the WP-CLI, run the following command:

```console
foo@bar:~$ php wp-cli.phar i18n make-pot . languages/better-images.pot
```

## How to do code style and syntax check (must have phpcs + WP code style installed)

```console
foo@bar:~$ phpcs --standard=WordPress better-images.php
```
