Live Profiler UI
================

![logo](images/liveprofui_logo.png "logo")

[Live profiler](https://github.com/badoo/liveprof) is a system-wide performance monitoring system in use at Badoo that is built on top of [XHProf](http://pecl.php.net/package/xhprof) or its forks ([Uprofiler](https://github.com/FriendsOfPHP/uprofiler) or [Tideways](https://github.com/tideways/php-profiler-extension)).
Live Profiler continually gathers function-level profiler data from production tier by running a sample of page requests under XHProf.

Live profiler UI aggregates the profile data corresponding to individual requests by various dimensions such a time, memory usage, and can help answer a variety of questions such as:
What is the function-level profile for a specific page?
How expensive is function "foo" across all pages, or on a specific page?
What functions regressed most in the last day/week/month?
What is the historical trend for execution time of a page/function? and so on.

You can find the full documentation in [Live Profiler UI wiki](https://github.com/badoo/liveprof-ui/wiki)

Here is [a plugin](https://plugins.jetbrains.com/plugin/13767-live-profiler) for PhpStorm to see the method performance directly in IDE.

[![Build Status](https://travis-ci.org/badoo/liveprof-ui.svg?branch=master)](https://travis-ci.org/badoo/liveprof-ui)
[![GitHub release](https://img.shields.io/github/release/badoo/liveprof-ui.svg)](https://github.com/badoo/liveprof-ui/releases/latest)
[![codecov](https://codecov.io/gh/badoo/liveprof-ui/branch/master/graph/badge.svg)](https://codecov.io/gh/badoo/liveprof-ui)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/badoo/liveprof-ui/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/badoo/liveprof-ui/?branch=master)
[![GitHub license](https://img.shields.io/github/license/badoo/liveprof-ui.svg)](https://github.com/badoo/liveprof-ui/blob/master/LICENSE)

System Requirements
===================

* PHP version 7.3 or later to use web interface and run aggregation scripts. 
* PHP version 5.4 or later / hhvm version 3.25.0 or later to collect profiles using [Live Profiler](https://github.com/badoo/liveprof)
* Connection to database with profiling result. You can collect profiles using [Live Profiler](https://github.com/badoo/liveprof) tool
* Database extension (mysqli, pgsql, sqlite support included)
* Perl for flame graph functionality

Key features
============

* Get stats of average value, minimum, maximum, 95 percentile of execution time, cpu time, memory usage and calls count. 
  Parameter list and statistics functions are configurable.
* Graphs for every collected parameter and every method up to 6 months. Each graph also includes children stats. It helps to see the history of changes.   
* Differences interface to compare a particular request for two dates and see what became worse.
* See [flame graph](http://www.brendangregg.com/flamegraphs.html) of the aggregated request.
* Get list of requests where a method was called last time. It may be helpful for refactoring purposes and find unused methods.
* Get the most changed methods in any requests for two dates, for example, today and a week ago. It can help to find a place of a potential performance problem. 

[Installation guide](https://github.com/badoo/liveprof-ui/wiki/Installation)
============================================================================
* [Run in the Docker container](https://github.com/badoo/liveprof-ui/wiki/Installation#Run-in-the-Docker-container)
* [Clone git repository](https://github.com/badoo/liveprof-ui/wiki/Installation#Clone-git-repository)

Work flow
=========

Live Profiler has 3 main parts:
1. [Profiler](https://github.com/badoo/liveprof-ui/wiki/Profiles-collection)
2. [Aggregator](https://github.com/badoo/liveprof-ui/wiki/Aggregation)
3. [Web interface](https://github.com/badoo/liveprof-ui/wiki/Web-interface)
    * [Profile list](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Profile-list)
    * [Methods tree page](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Methods-tree)
    * [Method list](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Method-list)
    * [Snapshots comparison interface](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Snapshots-comparison-interface)
    * [Flame graph](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Flame-graph)
    * [Find method usage](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Find-method-usage)
    * [Most changed snapshots](https://github.com/badoo/liveprof-ui/wiki/Web-interface#Most-changed-snapshots)

[Performance investigation guide](https://github.com/badoo/liveprof-ui/wiki/Performance-investigation-guide)
============================================================================================================

[Customisation](https://github.com/badoo/liveprof-ui/wiki/Customisation)
========================================================================

[Troubleshooting](https://github.com/badoo/liveprof-ui/wiki/Troubleshooting)
============================================================================

License
=======

This project is licensed under the MIT open source license.
