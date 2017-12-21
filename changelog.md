# Changelog

## Version {next}
  - Tests
  - Refactor
  - More tests, seriously, code-coverage to now above 90%

## Version 0.13.1 (2017-11-20)
  - A small fix when adding a new bookmark

## Version 0.13 (2017-11-17)
  - Update Browsershot to v3, uses puppeteer to control Chrome
  - Improve bookmarks syndication

## Version 0.12.6.1 (2017-11-13)
  - `.1` fixes a typo
  - Fix issue with generating image links from images uploaded to `/api/media`

## Version 0.12.5 (2017-11-09)
  - Fix style of tags on bookmarks page that had been visited
  - Fix style of notes listed on `/notes/tagged/tag`
  - Move code manging tagging of notes to NoteObserver

## Version 0.12.4 (2017-11-07)
  - Pull in newer version of my linkify extension to fix errors

## Version 0.12.3 (2017-11-07)
  - Add a link to the `colours.js` so the colour scheme can be changed

## Version 0.12.2 (2017-11-07)
  - Limit screen size of images in notes

## Version 0.12.1 (2017-11-07)
  - Change font

## Version 0.12 (2017-11-07)
  - New style
    - Here we improve the sass code as well, better modularisation
    - Colour schemes are now selectable and stored in the session
    - Added a typekit font again

## Version 0.11.2 (2017-10-22)
  - This hotfix removes reference to a dev package not installed on production

## Version 0.11.1 (2017-10-22)
  - Improve eloquent queries for rendering notes with contacts

## Version 0.11 (2017-10-19)
  - No more built-in micropub client

## Version 0.10 (20017-10-13)
  - Bookmarks!
    - They can only be added via micropub
    - A screenshot is taken
    - The page is saved to the internet archive

## Version 0.9 (2017-10-06)
  - Add support for `likes` (issue#69)
  - Only included links on truncated syndicated notes https://brid.gy/about#omit-link

## Version 0.8.1 (2017-09-16)
  - Order notes by latest (issue#70)
  - AcitivtyStream support is now indicated with HTTP Link headers

## Version 0.8 (2017-09-16)
  - Improve embedding of tweets (issue#66)
  - Allow for “responsive” images (issue#62)

## Version 0.7.3 (2017-09-13)
  - Fix a test

## Version 0.7.2 (2017-09-13)
  - Small AS2.0 improvements

## Version 0.7.1 (2017-09-13)
  - Add content-negotiated AS data for homepage and single notes

## Version 0.7 (2017-09-08)
  - Add Laravel Horizon

## Version 0.6 (2017-09-06)
  - Update laravel version to 5.5
  - Improve .travis.yml and add back dusk tests

## Version 0.5.28 (2017-08-20)
  - Improve Swarm checkin code to allow checkins without text
    + this required a change in the notes table schema
  - Improve code by bringing in latest changes in laravel/laravel
  - Improve POSSE compatability with bridgy and silos

## Version 0.5.27 (2017-07-24)
  - Just a bump in dependency versions used

## Version 0.5.26 (2017-07-20)
  - Remove some file checking to see if we can get uploads working again

## Version 0.5.25 (2017-07-20)
  - Update npm dev dependencies to use local installs over global installs
  - Improve contact info display in note mentions by using hovercards
  - Add some error messages when trying to upload media to aid debugging

## Version 0.5.24 (2017-07-13)
  - Add my `commonmark-linkify` extension
  - Some minor tweaks, including logging of micropub media requests

## Version 0.5.23 (2017-07-07)
  - Add emoji 5.0 support with newer `emoji-a11y` package
  - Places can be “added” to a note in the mp-client again, (issue#47)

## Version 0.5.22 (2017-07-06)
  - A small improvement to the piwik tracking script

## Version 0.5.21 (2017-07-04)
  - Better logging of micropub requests
  - fix a style issue with images

## Version 0.5.20 (2017-06-30)
  - Transition to using a JSON column for external urls of places

## Version 0.5.19 (2017-06-27)
  - Fix error in App\\WebMention.php

## Version 0.5.18 (2017-06-23)
  - Minor change in deploy script to speed things up

## Version 0.5.17 (2017-06-22)
  - Lots of code tidying, especially in the notes controller
  - Fix issue#53 regarding uploading photos

## Version 0.5.16 (2017-06-17)
  - Allow place `slug`s to be re-generated
  - Add syndication links for swarm and instagram
  - Move bio to its own template, next step database?

## Version 0.5.15 (2017-06-17)
  - Add support for ownyourgram.com sending h-card locations
  - change sluggable implementation
  - Add tests for uploading new articles from .md files
  - Fix issue with maps not loading geojson data

## Version 0.5.14 (2017-06-11)
  - Remove some Log statements in-appropriate for porduction

## Version 0.5.13 (2017-06-11)
  - Fix issues around using ownyourgram.com

## Version 0.5.12 (2017-06-11)
  - Add ability to delete notes

## Version 0.5.11 (2017-06-11)
  - to help with micropub clients, log requests made to micropub endpoint

## Version 0.5.10 (2017-06-09)
  - Add a link to instagram account
  - Add syndication feeds for articles/notes, supporting RSS/Atom/JSON (issue#52)

## Version 0.5.9 (2017-05-31)
  - Mapping improvements
  - Basic place merging

## Version 0.5.8 (2017-05-21)
  - Hotfix: if Carbon can’t parse the supplied published date of a webmention, use the Model’s `updated_at` value

## Version 0.5.7 (2017-05-19)
  - Hotfix: make sure `mpSyndicateTo` variable exists when accessed in if statements

## Version 0.5.6 (2017-05-19)
  - Update micropub code to support html-form and json syntax for mp-syndicate-to and photos

## Version 0.5.5 (2017-05-19)
  - improve test suite
  - Syndication should now work

## Version 0.5.4 (2017-05-18)
  - Fix issues with using the indieauth client

## Version 0.5.3 (2017-05-18)
  - Tweak config page and get token method to better handle/show errors

## Version 0.5.2 (2017-05-18)
  - Fix variable issues in making client page

## Version 0.5.1 (2017-05-18)
  - Fix issue on micropub create page when not logged in

## Version 0.5 (2017-05-18)
  - Update micropub client to allow indieweb users
  - Update micropub endpoint to allow for entry updates
  - Add support for checkins, so we can use ownyourswarm

## Version 0.4.2 (2017-03-24)
  - fixed issue#47, only the slug was being sent by client, which was messing up endpoint code
  - minor changes to es6 code, bet lint-staged working again
  - Make processed article content its own fake attribute, articles can now be uploaded as a file

## Version 0.4.1 (2017-03-18)
  - Improve HTML Purification, target=blank rel-nofollow and rel-noopener should
now be added to external links
  - Better handling of javascript compilation/minification and source-map generation

## Version 0.4 (2017-03-18)
  - Media endpoint added

## Version 0.3.6 (2017-03-07)
  - Pull in Piwik’s own piwik.js manually, again for CSP

## Version 0.3.5 (2017-03-07)
  - Move piwik code into its own js file to allow for CSP

## Version 0.3.4 (2017-03-07)
  - Remove document.write to allow CSP to work

## Version 0.3.3 (2017-03-03)
  - Fix issue when accessing /admin

## Version 0.3.2 (2017-03-03)
  - Remove route closures to allow route:cache-ing

## Version 0.3.1 (2017-03-03)
   - Correct command to restart daemon queues in deploy.sh
   - Improve Admin CP by “resource”-ifying the controllers

## Version 0.3 (2017-03-02)
  - convert env() calls to config() calls for cacheing
  - refactor routes and give important one names
  - Add Dusk tests
  - Add a deploy script
  - Add a .editorconfig file
  - Bump to PHP 7.1 to start using nullable return types and strict types

## Version 0.2.5 (2017-02-15)
  - Small fix for homepage bio, removed confusing un-needed view that caused fix to be necessary

## Version 0.2.4 (2017-02-15)
  - Make embedded youtube iframe a dynamic size
  - Add Piwik tracking code
  - Minor profile tweaks

## Version 0.2.3 (2017-02-05)
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
