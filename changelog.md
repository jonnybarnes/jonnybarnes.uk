# Changelog

## Version {next}
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
