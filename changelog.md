# Changelog

## Version {next}
  - Autolink/embed youtube videos and spotify links

## Version 0.2.2 (2017-02-05)
  - Fix: allow syndication to work again (issue#42)

## Version 0.2.1 (2017-02-03)
  - Add css for emoji labels

## Version 0.2 (2017-02-03)
  - Update `syndicate-to` property to `mp-syndicate-to`
  - Add my emoji-a11y dependency
  - Upgrade to Laravel 5.4

## Version 0.1.7 (2017-01-27)
  - Add a rel=me link to my own domain in my h-card.

## Version 0.1.6 (2017-01-27)
  - Update the webmention parser to a version with a verified fix

## Version 0.1.5 (2017-01-27)
  - Update the webmention parser version to fix a bug with displaying webmentions

## Version 0.1.4 (2017-01-27)
  - Fix: refactor code slightly to allow multiple maps to be added to a page

## Version 0.1.3 (2017-01-26)
  - cleanup frontend assets, update compressed versions

## Version 0.1.2 (2017-01-26)
  - Improve syndication flow when working out which targets to use
  - Use webpack/babel/es6 (this was a big one, code wise, functionality now basically the same though)

## Version 0.1.1 (2016-12-10)
  - Fix: use correct link for footer iwc icon

## Version 0.1 (2016-12-10)
  - Much better testing of micropub endpoints locally and on TravisCI
  - Updating README
  - Add IWC logo to footer

## Version 0.0.18 (2016-12-08)
  - Some minor style tweaks
  - Fix some validation issues
  - Switch to Makefile for front-end build tasks
  - Switch to Postgres based search
  - Update travis to use aforementioned search and php 7.1
  - Move syndication targets into a config file (issue#27)

## Version 0.0.17 (2016-11-25)
  - Add a basic search feature using Laravel Scout and Algolia (issue#38)
  - Get CI testing working with algolia
  - Slightly better layout of replies

## Version 0.0.16.3 (2016-11-25)
  - StyleCI fix

## Version 0.0.16.2 (2016-11-25)
  - improved contact h-cards
  - Better look in /contacts
  - h-cards now have person-tags (issue#36)
  - maps now have zoom controls (issue#37)

## Version 0.0.16.1 (2016-11-22)
  - Break words
  - Added a footer to all pages
  - Added a colophon page

## Version 0.0.16 (2016-11-22)
  - Much simpler website design
  - Update mapbox to use Mapbox GL JS, things can be improved
  - Make the homepage show notes, as well as bio (issue#16)

## Verison 0.0.15.13 (2016-11-08)
  - Link to the source of a reply correctly (issue#33)

## Version 0.0.15.12 (2016-11-07)
  - Fix micropub client in-reply-to name

## Version 0.0.15.11 (2016-11-07)
  - Fix send webmention

## Version 0.0.15.10 (2016-11-07)
  - Update typekit’s sri hash

## Version 0.0.15.9 (2016-11-07)
  - Hotfix: not using cerated variable of foreach loop

## Version 0.0.15.8 (2016-11-07)
  - Hotfix: facebook’s love-of appears as an in-reply-to without a published date

## Version 0.0.15.7 (2016-11-07)
  - Add a reply icon in note metadata
  - Allow notes to be deleted

## Version 0.0.15.6 (2016-11-03)
  - Remove reply/like/repost links, not needed without indie-action
  - Add facebook syndication link (issue#29)

## Version 0.0.15.5 (2016-10-31)
  - Fix: update note view to use longitude in h-card for a place

## Version 0.0.15.4 (2016-10-26)
  - Use an array with `syndicate-to` to allow multiple values

## Version 0.0.15.3 (2016-10-26)
  - Fix: didn’t import the namespace for the facebook job

## Version 0.0.15.2 (2016-10-26)
  - Fix: syntax error introduced in v0.0.15.1

## Version 0.0.15.1 (2016-10-26)
  - Add facebook as a syndication target

## Version 0.0.15 (2016-10-26)
  - Modify SyndicateToTwitter to use bridgy publish
  - Add a SyndicateToFacebook job which also uses bridgy publish (issue#24)
  - Modify views to facilitate bridgy publish (issue#26)

## Version 0.0.14.13 (2016-10-26)
  - Fix: correct the syntax of Link headers (issue#25)

## Version 0.0.14.12 (2016-10-24)
  - Attempt to fix some HTML validation issues

## Version 0.0.14.11 (2016-10-24)
  - Having used `yarn` for npm packages, we now also use it for bower packages
  - Update typekit sri hash
  - Hide co-ordinates, in data tags, we want them to be read by machines, but not humans
  - Use `h-card` for “places”, and`h-adr` for reverse lookup location name

## Version 0.0.14.10 (2016-10-21)
  - Fix: Trying to get brid.gy markup compatibility

## Version 0.0.14.9 (2016-10-21)
  - Include co-ordinates with notes and markup with appropriate microformats
  - Add correct microformats for photos

## Version 0.0.14.8 (2016-10-20)
  - Use the correct namespace

## Version 0.0.14.7 (2016-10-20)
  - Add needed namespace (issue#23)

## Version 0.0.14.6 (2016-10-20)
  - issue#23 again, also pinning against a tagged webmentions-parser release

## Version 0.0.14.5 (2016-10-20)
  - Fix an issue in the save profile image job (issue#23)

## Version 0.0.14.4 (2016-10-19)
  - Fix a bad explode() call in the syndicate job

## Version 0.0.14.3 (2016-10-19)
  - Allow co-ordinates to be used for note location, reverse geocode place name will be used (w/o map)
  - Switch from npm to yarn

## Version 0.0.14.2 (2016-10-17)
  - Update .lock, particularly trying to get medialibrary working

## Version 0.0.14.1 (2016-10-10)
  - Allow files uploaded to the client to be sent to the endoint without needing to use `media-tmp`

## Version 0.0.14 (2016-10-07)
  - Fix image upload for notes
  - Allow co-ordinates to be sent by the client as a geo: URI
  - Allow endpoint to process geo: URIs for location

## Version 0.0.13.9 (2016-10-06)
  - Hotfix, add missing semi-colon

## Version 0.0.13.8 (2016-10-06)
  - Create a Place model instance in SyndicateToTwitter job to force laravel to access postgis methods

## Version 0.0.13.7 (2016-10-05)
  - Use the correct `laravel-postgis` method call during syndication

## Version 0.0.13.6 (2016-10-05)
  - Syndicate lat/lng values (issue#22)

## Version 0.0.13.5 (2016-10-05)
  - Places can now be added to a new note created via micropub

## Version 0.0.13.4 (2016-10-03)
  - Better working code for places in newnote.js (issue#21)
    * In aid of this add ability to run micropub code locally

## Version 0.0.13.3 (2016-10-03)
  - Use the actual results of places in `newnote.js` (issue#21)

## Version 0.0.13.2 (2016-10-03)
  - Fix issues with fetch API and places when using micropub client

## Version 0.0.13.1 (2016-10-01)
  - Add support for accuracy/uncertainty in geo URIs (issue#20,issue#9)
  - Add some places tests

## Version 0.0.13 (2016-09-26)
  - Better places support, particularly with micropub (issue#9)
  - Uglify javascript for better performance (issue#19)
  - Auto-link Spotify links (issue#18)

## Version 0.0.12 (2016-09-21)
  - Better indication of number of replies to a note (issue#17)
  - Use generic twitter status URL so my own profile name isn’t hardcoded (issue#14)

## Version 0.0.11.9 (2016-09-21)
  - Fix: Correctly parse microformats data for single note view

## Version 0.0.11.8 (2016-09-21)
  - Fix: remove index.html from generated url

## Version 0.0.11.7 (2016-09-21)
  - Fix: need to create necessary directories first

## Version 0.0.11.6 (2016-09-20)
  - Fix: save webmention HTML to correct location

## Version 0.0.11.5 (2016-09-20)
  - Fix job dispatching to more in line with Laravel 5.3 practices

## Version 0.0.11.4 (2016-09-19)
  - Better console output for the new webmention commands

## Version 0.0.11.3 (2016-09-19)
  - Simplify how we filter/cache reply html
  - Better handling of webmention reply HTML cache

## Version 0.0.11.2 (2016-09-19)
  - Update Typekit’s javascript sri hash

## Version 0.0.11.1 (2016-09-17)
  - Fix a syntax issue in the download webmention job

## Version 0.0.11 (2016-09-17)
  - update linked GPG key (issue#7)
  - Added `integrity` values to external assets (issue#10)
  - Move Mapbox links into own sub-view (issue#11)
  - Updated Mapbox version (issue#12)
  - Massive refactor of webmention code, allowing for re-parse command (issue#8)
  - Add license file (issue#13)

## Version 0.0.10 (2016-09-10)
  - Add an artisan command for Sensiolab’s security check
  - Remove `filp/whoops`, just use Laravel’s error reporting
  - Better TokenMismatchException handling (issue#5)

## Version 0.0.9.2 (2016-09-08)
  - Remove Piwik
  - Updated some bower dependencies
  - Tidy some `.git*` files

## Version 0.0.9.1 (2016-09-07)
  - Fix an issue with syndicating notes.

## Version 0.0.9 (2016-09-06)
  - Adding `jsonb` column to store webmentions’ microformats.
    * As of L5.2 this needs a custom command to drop NOT NULL from content, L5.3 should allow a fix for this
  - Refactor receiving webmention code
  - Refactor sending webmention code to pass `webmention.rocks`
  - Update to use Laravel 5.3

## Version 0.0.8.5 (2016-07-18)
  - Set the size of the `textarea` in a form better
  - Update to latest Guzzle to fix CVE-2016-5385

## Version 0.0.8.4 (2016-07-18)
  - Make the revised non-elixir asset links absolute

## Version 0.0.8.3 (2016-07-18)
  - Dump `laravel-elixir`, use gulp natively. Also this means using nginx and etags for cache-busting

## Version 0.0.8.2 (2016-07-15)
  - Improve syndication parsing to allow better name display on new note form

## Version 0.0.8.1 (2016-07-13)
  - Fix an issue in the syndication target parsing method

## Version 0.0.8 (2016-07-13)
  - Allow new notes to be made by a JSON request from a micropub client
  - Add DependencyCI support

## Version 0.0.7.1 (2016-07-04)
  - Minor style fixes

## Version 0.0.7 (2016-07-04)
  - Use JSON for syndication endpoint query response
  - Use JSON for all micropub requests
  - Add support for `q=config` query of the micropub endpoint

## Version 0.0.6.3 (2016-06-29)
  - Fix an issue with dispatching the syndication job

## Version 0.0.6.2 (2016-06-28)
  - Fix an issue with sending webmentions

## Version 0.0.6 (2016-06-28)
  - Better use of `laravel-postgis`
  - Change style for inline mini-profile images

## Version 0.0.5 (2016-06-23)
  - Automatically send webmentions
  - Change `mp-syndicate-to` to `syndicate-to`

## Version 0.0.4 (2016-06-21)
  - Move bower components into their own subdir
  - Move my js into `resources/`, apply an eslint pre-commit hook
  - Better guplfile, next thing is to add cleanup of old compressed files
  - Update `spatie/laravel-medialibrary` to v4, tweak associated code
  - Merge in upstream changes
  - Add a stylelint lint-staged hook

## Version 0.0.3 (2013-06-09)
  - Better tag normalisation code organisation
  - Remove `jonnybarnes/unicode-tools` dependency and clean up relevant code

## Version 0.0.2 (2016-05-25)
  - Fix issue#1: tagged notes page needs the tag from the URL normalising.

## Version 0.0.1 (2016-05-25)
  - Initial release
