# WP DFP

A simple & intuitive interface for displaying Google ads on your WP site

### Dependencies

This project depends on NPM.

Don't have NPM? Visit the [Getting Started](https://docs.npmjs.com/getting-started/installing-node) guide for step-by-step instruction.

### Installation

1. Clone this repo to your local environment
2. Run `npm install`
3. Create a symlink in your WordPress plugins folder that points to the `wp-dfp` directory within your cloned repo. For example:

```shell
ln -s /path/to/repo/wp-dfp /path/to/wp-content/plugins/wp-dfp
```

### To work in your development environment:

	$ gulp clean

    $ gulp develop

**NOTE:** The `clean` step is necessary before the `develop` step, in order to clean up the copied externals. It is necessary to run them in order separately (as opposed to simply making `clean` a dependency of `develop` in the Gulpfile,) due to a race-condition due to the asynchronous nature of gulp's task runner. Any effort to solve this bug would be greatly appreciated.


