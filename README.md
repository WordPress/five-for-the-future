# Five for the Future (WordPress.org/five-for-the-future)

[Five for the Future](https://wordpress.org/five-for-the-future) is an initiative promoting the WordPress community’s contribution to the platform’s growth. As an open source project, WordPress is created by a diverse collection of people from around the world.

The program encourages organizations to contribute five percent of their resources to WordPress development, to maintain a "golden ratio" of contributors to users.


## Contributing

In order to contribute with code changes, you'll want to set up a local environment to test changes and then push the changes to a Pull Request on this Github Repository.


### Prerequisites

* Docker
* Node (v20), npm
* Composer

⚠️ Note: this repo does not use Yarn, it uses vanilla npm. You should be using Node 20 (LTS), for example at the time of writing, the current version is 20.17.0, which comes with npm 10.8.2. Modern versions of npm have workspace features similar to how we already use yarn.


### Initial environment setup

1. Fork the [five-for-the-future](https://github.com/WordPress/five-for-the-future) repository under your own Github account.
1. Run `git clone git@github.com:[your username]/five-for-the-future.git wp-content`, replacing `[your username]` with your github username to clone your forked repo.
1. Set up repo dependencies.

	```bash
	npm run setup:tools
	```

1. Build the project.

	```bash
	npm run build:theme
	```

1. Start the local environment.

	```bash
	npm run wp-env start
	```

1. Configure WordPress.

	```bash
	npm run setup:wp
	```

If you're using a different local environment, or don't want to use wp-env, you can skip that step and just replace `wp-content` with this repo, so that the themes and plugins are in the correct places.


### Configuring the site

1. Login to your site and activate the "Five for the Future" theme and plugin.


### Setting up default data

1. Save your permalinks: `Settings > Permalinks`.
1. Configure your first pledge.
	1. Visit `Five for the future > Pledges`.
	1. Add `Administrator Email Address` -> Any address.
	1. Add `Organization Name` -> Any name.
	1. Add `Website Address` -> Any web address.
	1. Add `Organization Blurb` -> Any content.
	1. Add `Contributor Usernames` -> Use any of the users listed in `wp-admin/users.php` excluding admin.
	  1. Technically you can use any wp.org user but we use dummy data added in `./env/bpmain_bp_xprofile_data.sql` which maps to these user IDs.
	1. Set a `featured image`.
	1. Click `Update` to publish draft pledge. 
	1. Visit `Five for the future > Contributors`.
		1. Click on any `Pending` contributors and `Publish` them.

## Scripts

If you making changes to the theme's CSS, you can run `npm start` at `/wp-content/themes/wporg-5ftf` to watch for CSS changes and automatically compile.

If you are making changes to the plugins, you can run `composer update` at `/wp-content/plugins/wporg-5ftf` and then `composer run test` to run the WP unit tests. Run `composer test:watch` if you want to run the tests every time you change a file.

And lastly, you can run PHPCS for both the theme and the plugin at the root `/wp-content/` folder by running `composer install` there once, followed by `composer run phpcs` when you want to code scan.


* `composer run lint` - Lint the entire codebase
* `composer run lint -- -a themes/wporg-5ftf/` - Lint a specific folder, interactively
* `composer run lint $(pwd)/inc*/ac*` - List file(s) in the current directory without typing the full path
* `composer run format` - Fix linter warnings (when possible)
* `composer run test` - Run unit tests
* `composer run test:watch` - Run unit tests after each file change.

See [the theme README](./themes/wporg-5ftf/README.md) for scripts specific to the theme.



### Submitting Pull Requests

The first thing you'll want to do before changing any code is create a new branch based on the `production` branch. Then you can commit your code changes locally and push this new branch to your forked repository on Github. Then visit the [official repository](https://github.com/WordPress/five-for-the-future/) and you should see the option to open up a Pull Request based on the recently pushed branch on your fork.

Overtime your fork will fall out of date with what is on the main repository. What you'll want to do is keep your fork's `production` branch synced with the upstream `production` branch. To do this:

1) In the `/wp-content/` folder, run `git remote add upstream https://github.com/WordPress/five-for-the-future`
2) Then `git fetch upstream` to pull down the upstream changes.
3) Lastly, `git checkout production && git merge upstream/production` to sync up the your local branch with the upstream branch.

This is why it's important to always create a branch on your local fork before making code changes. You want to keep the `production` branch clean and in sync with the upstream repository.


## Syncing to production

This is only relevant for committers; contributors don't need to worry about syncing.

The canonical source for this project is [github.com/WordPress/five-for-the-future](https://github.com/WordPress/five-for-the-future). The contents are synced to the dotorg SVN repo to run on production, because we don't deploy directly from GitHub, for reliability reasons.

The plugin and theme lives in the private SVN repo instead of `meta.svn.wordpress.org`, because the code is already open-sourced, and we don't want to clutter the Meta logs and Slack channels with noise from "Syncing w/ Git repository..." commits.

To sync to SVN, run `bin/sync/5ftf.sh` from a w.org sandbox.
