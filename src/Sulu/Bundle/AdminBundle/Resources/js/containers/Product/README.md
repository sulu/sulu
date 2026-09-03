# Product containers

Product-specific admin UI for the `sulu/product-bundle` package.

This code lives in sulu core, not in the bundle, because sulu ships a prebuilt admin build for
tagged releases: a bundle carrying its own JavaScript would force every consuming project into a
custom admin build. The same reasoning as `containers/AiApplication`.

> **NOTE:** These containers are experimental, can change at any time and are not covered by our
> BC promise.

The domain, the API and the entities live in `sulu/SuluProductBundle`. A change to the attributes
API needs a matching sulu release.
