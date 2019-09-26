### WordPress.org Five for the Future theme

WordPress theme for [the Five for the Future subsite](https://wordpress.org/five-for-the-future).


## Developing

```
npm install
grunt watch
```

Make CSS changes in the `css/` folder, and `css/style.css` will be rebuilt automatically.


## Committing

Before committing changes to `css/`, please run `grunt build` to keep the file size down.


## Miscellaneous

The canonical source for this project is [github.com/WordPress/five-for-the-future](https://github.com/WordPress/five-for-the-future). The contents are synced to the dotorg SVN repo to run on production, because we don't deploy directly from GitHub, for reliability reasons. 

The production copy lives in `themes/` instead of `themes/pub`, because it's already open in GitHub, and we don't want to clutter the Meta repo logs and Slack channels with noise from "Syncing w/ Git repository..." commits.
