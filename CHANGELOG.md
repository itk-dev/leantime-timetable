# Changelog

## [Unreleased]

* [PR-6](https://github.com/ITK-Leantime/leantime-timetable/pull/6)
  * Added styling throughout plugin
  * Rewritten timeTable.js into a class, overhauled methods and added doccomments
  * Added separate apihandler used by timetable.js
  * Modified blade template to better match design
  * Loading apihandler frontend
  * Added apihandler symlink to install script
  * Added additional data as well as a get hook for jQuery ajax
  * Added left join to project table in order to get project name with worklogs

* [PR-4](https://github.com/ITK-Leantime/leantime-timetable/pull/4)
  * Adds get/posts in the controller. post for saving, get for getting/sorting/organizing data
  * sql in repository, as the sql in the code base did not cover our use case
  * Create a basic table in blade-file

* [PR-3](https://github.com/ITK-Leantime/leantime-timetable/pull/5)
  * Added build release script
  * Added deploy script
  * Add `%%VERSION%%`, that is replaced by create-release script, to make [leantime load new script/css files](https://www.keycdn.com/support/what-is-cache-busting)
  * Add `release.yml`, that builds both release and pre-releases on push tag/branch
  * Add shellcheck to `pr.yml`

* [PR-2](https://github.com/ITK-Leantime/leantime-timetable/pull/2)
  * Setup code analysis
  * Setup translations
  * Setup github actions
  * Setup linting
  * Create a changelog

* [PR-1](https://github.com/ITK-Leantime/leantime-timetable/pull/1)
  * Foundation for further development
