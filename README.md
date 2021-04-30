# Five for the Future (WordPress.org/five-for-the-future)

[Five for the Future](https://wordpress.org/five-for-the-future) is an initiative promoting the WordPress community’s contribution to the platform’s growth. As an open source project, WordPress is created by a diverse collection of people from around the world.

The program encourages organizations to contribute five percent of their resources to WordPress development, to maintain a "golden ratio" of contributors to users.


## Scripts

* `composer run phpcs` - Lint the entire codebase
* `composer run phpcs -- -a themes/wporg-5ftf/` - Lint a specific folder, interactively
* `composer run phpcbf` - Fix linter warnings (when possible)
* `composer run test` - Run unit tests

See [the theme README](./themes/wporg-5ftf/README.md) for scripts specific to the theme.


## Syncing to production

The canonical source for this project is [github.com/WordPress/five-for-the-future](https://github.com/WordPress/five-for-the-future). The contents are synced to the dotorg SVN repo to run on production, because we don't deploy directly from GitHub, for reliability reasons.

The plugin and theme lives in the private SVN repo instead of `meta.svn.wordpress.org`, because the code is already open-sourced, and we don't want to clutter the Meta logs and Slack channels with noise from "Syncing w/ Git repository..." commits.
