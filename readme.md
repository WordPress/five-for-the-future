# Five for the Future

Plugins and themes for the Five for the Future subsite: https://wordpress.org/five-for-the-future/

## Contributing

In order to contribute with code changes, you'll want to set up a local environment to test changes and then push the changes to a Pull Request on this Github Repository.

### Initial environment setup

1) Use whichever local WordPress development setup you prefer and create a new local WP site.
2) Find the `wp-content` folder and delete it (make a backup if you have data you don't want to lose data you already have there).
3) Fork the [five-for-the-future](https://github.com/WordPress/five-for-the-future) repository under your own Github account.
4) Run `git clone git@github.com:[your username]/five-for-the-future.git wp-content`, replacing `[your username]` with your github username to clone your forked repo.
5) Ensure this newly cloned `wp-content` folder is where it should be in the WP structure.
6) Copy over the base theme with: `svn export https://meta.svn.wordpress.org/sites/trunk/wordpress.org/public_html/wp-content/themes/pub/wporg themes/pub/wporg` (this should be run from the `wp-content` folder).

### Configuring the site

1) Login to your site and activate the "Five for the Future" theme and plugin.
2) Navigate to `/wp-content/themes/wporg-5ftf` and run: `npm install && npm run build`

### Setting up default data

1) Set your permalinks to "Post name" at Settings > Permalinks.
2) Run the WP XML Importer at Tools > Import and [import this xml file](#TODO).
3) Set the Primary Menu at Appearance > Menu.
4) Set "About" as the static home page at Settings > Reading. 
5) Add new Pledges on the "Add New Pledge" page. Note that you'll need to use valid WP usernames on your install.
	- 5.1) Set the new entry to Published in the Five For the Future > Pledges admin area.
	- 5.2) Find the "Sending email" log entry in the pledge admin and copy/paste the link in a new tab to confirm the email.
	- 5.3) Go to the Five For the Future > Contributors page and publish the post(s) via quick edit.
	- 5.4) Your new pledge should appear on the `/pledges/` pages now.

### Running build scripts and tests 

If you making changes to the theme's CSS, you can run `npm start` at `/wp-content/themes/wporg-5ftf` to watch for CSS changes and automatically compile.

If you are making changes to the plugins, you can run `composer install` at `/wp-content/plugins/wporg-5ftf` and then `composer run test` to run the WP unit tests.

And lastly, you can run PHPCS for both the theme and the plugin at the root `/wp-content/` folder by running `composer install` there once, followed by `composer run phpcs` when you want to code scan. 

### Submitting Pull Requests

The first thing you'll want to do before changing any code is create a new branch based on the `production` branch. Then you can commit your code changes locally and push this new branch to your forked repository on Github. Then visit the [official repository](https://github.com/WordPress/five-for-the-future/) and you should see the option to open up a Pull Request based on the recently pushed branch on your fork.

Overtime your fork will fall out of date with what is on the main repository. What you'll want to do is keep your fork's `production` branch synced with the upstream `production` branch. To do this:

1) In the `/wp-content/` folder, run `git remote add upstream https://github.com/WordPress/five-for-the-future`
2) Then `git fetch upstream` to pull down the upstream changes.
3) Lastly, `git checkout production && git merge upstream/production` to sync up the your local branch with the upstream branch.

This is why it's important to always create a branch on your local fork before making code changes. You want to keep the `production` branch clean and in sync with the upstream repository.
