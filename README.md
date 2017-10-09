# WP CLI PHAR

This package provides the PHAR releases for [WP CLI](https://github.com/wp-cli/wp-cli) as their own composer package. This allows you to require a version of wp-cli as a dependency without having all of *its* dependencies all over your vendor directory. This package exposes the phar file as a binary to composer, so you can depend on it being available to your composer scripts, no matter where you've deployed your code.

## File Integrity

All PHAR files are checked for a valid signature against the sha512 signature attached to the relevant release. Feel free to check the signature yourself.

## LICENSE

MIT licensed (just like WP CLI).
