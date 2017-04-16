SuluSecurityBundle
=================
[![](https://travis-ci.org/sulu-cmf/SuluSecurityBundle.png)](https://travis-ci.org/sulu-cmf/SuluSecurityBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sulu-cmf/SuluSecurityBundle/badges/quality-score.png?s=ac25ffe49e2a961abc6e4c978f5f8e52e55410ff)](https://scrutinizer-ci.com/g/sulu-cmf/SuluSecurityBundle/)

This bundle is part of the [Sulu Content Management Framework](https://github.com/sulu-cmf/sulu-standard) (CMF) and licensed under the [MIT License](https://github.com/sulu-cmf/SuluSecurityBundle/blob/develop/LICENSE).

The SuluSecurityBundle builds on other sulu-cmf bundles. It provides a solution to create/modify roles in sulu.

## Features

* Roles editing
* User management (extension of contacts)
* Facilities to check permissions

## Requirements

* Symfony: 2.3.*
* Sulu: dev-master
* See also the require section of [composer.json](https://github.com/sulu-cmf/SuluSecurityBundle/blob/develop/composer.json)

## PHP Unit Testing

    phpunit

## PHP Mess Detector

    phpmd . xml codesize,controversial,design,unusedcode --exclude vendor,Tests
