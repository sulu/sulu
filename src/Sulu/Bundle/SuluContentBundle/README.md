SuluContentBundle
=================
[![](https://travis-ci.org/sulu-cmf/SuluContentBundle.png)](https://travis-ci.org/sulu-cmf/SuluContentBundle)

This bundle is part of the [Sulu Content Management Framework](https://github.com/sulu-cmf/sulu-standard) (CMF) and licensed under the [MIT License](https://github.com/sulu-cmf/SuluContentBundle/blob/develop/LICENSE).

The SuluContentBundle builds on other sulu-cmf bundles. It provides a solution to create/modify content nodes in sulu. It uses the ContentMapper functionality from [sulu-lib](https://github.com/sulu-cmf/sulu).

## Features

* Preview
  * Preview to live edit Content-Node in real template
  * Uses Websocket for Communication
  * Provides a Fallback with polling

## Requirements

* Symfony: 2.3.*
* Sulu: dev-master
* [Ratchet Websocket Library](https://github.com/cboden/ratchet): dev-master
* See also the require section of [composer.json](https://github.com/sulu-cmf/SuluContentBundle/blob/develop/composer.json)
