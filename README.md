# better-images

A WordPress plugin for Better Images.

## How to generate/update .pot file:

From the WP-CLI, run the following command:

```console
foo@bar:~$ php wp-cli.phar i18n make-pot . languages/better-images.pot
```

## How to do code style and syntax check (must have phpcs + WP code style installed)

```console
foo@bar:~$ phpcs --standard=WordPress better-images.php
```
