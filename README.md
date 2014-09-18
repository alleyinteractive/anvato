anvato
======

A WordPress Plugin for integrating the Anvato video player.

# Shortcode

## Basic shortcode usage

`[anvplayer video="282411"]`

### Available shortcode attributes

* `video`
* `autoplay`
* `adobe_analytics` (accepts only `false`, which removes all Adobe settings from the output)
* `seek_to` (in seconds)

#### Available attributes that override default settings

* `mcp`
* `station_id`
* `profile`
* `width`
* `height`
* `player_url`
* `plugin_dfp_adtagurl` (also accepts `false`, which removes it from the output)
* `tracker_id` (also accepts `false`, which removes it from the output)
* `adobe_profile` (also accepts `false`, which removes it from the output)
* `adobe_account` (also accepts `false`, which removes it from the output)
* `adobe_trackingserver` (also accepts `false`, which removes it from the output)