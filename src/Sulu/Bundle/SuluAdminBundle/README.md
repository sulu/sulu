SuluAdminBundle
=================
[![](https://travis-ci.org/sulu-cmf/SuluAdminBundle.png)](https://travis-ci.org/sulu-cmf/SuluAdminBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sulu-cmf/SuluAdminBundle/badges/quality-score.png?s=c37fee3b601e56aa5f15d82f0dfbc706271643b0)](https://scrutinizer-ci.com/g/sulu-cmf/SuluAdminBundle/)

This bundle is part of the [Sulu Content Management Framework](https://github.com/sulu-cmf/sulu-standard) (CMF) and licensed under the [MIT License](https://github.com/sulu-cmf/SuluAdminBundle/blob/develop/LICENSE).

The SuluAdminBundle builds on other sulu-cmf bundles.

## Features
* General UI for Sulu Administration
* Service for including own bundles in Sulu navigation

## Requirements

* Symfony: 2.3.*
* Sulu: dev-master
* See also the require section of [composer.json](https://github.com/sulu-cmf/SuluAdminBundle/blob/develop/composer.json)

## Development

### Installation

1. Install SuluAdminBundle with composer into a symfony project
1. Install all the node modules with `npm install` (only require for grunt)

### Live Development
There is a grunt task available to make editing and immediate testing of javascript files possible.
Assuming that you have grunt installed you can let run the task with `grunt watch --force` in the root directory.
All files will be copied to the correct location after you have edited them.

### Building a production version
With the command `grunt build` you can build a new production version. All files will be optimized, and saved in a new location.
That is `Resources/public/dist` for javascript and css, and a new template, which uses the optimized files in `Resources/views/Admin/index.html.dist.twig`.



### PHP Unit Testing

    phpunit


### PHP Mess Detector

    phpmd . xml codesize,controversial,design,unusedcode --exclude vendor,Tests
