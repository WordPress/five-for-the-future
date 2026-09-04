#!/bin/bash

root=$( dirname $( wp config path ) )

wp db import "${root}/env/bpmain_bp_xprofile_data.sql"

wp theme activate wporg-5ftf-2024
wp plugin activate wporg-5ftf

wp rewrite structure '/%postname%/'
wp rewrite flush

wp option update blogname "Five for the Future"

wp plugin activate wordpress-importer

wp import "${root}/env/import.wxr" --authors=create

wp option update show_on_front 'page'
wp option update page_on_front 6

## Create a sample pledge since you can't do it in the admin.
wp post create --post_type=5ftf_pledge --post_title="Sample Pledge"


