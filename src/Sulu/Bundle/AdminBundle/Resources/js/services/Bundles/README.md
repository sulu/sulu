The job of the Bundles service is to offer methods to guarantee that all index JavaScript files of every bundle
is already loaded before the application gets initialized.

When all bundle JavaScript files are loaded the `bundlesReadyPromise` gets resolved and that is where the
application gets started. We also call `bundleReady` to signalize that the AdminBundle is also ready.

```js
import {bundlesReadyPromise, bundleReady} from './services/Bundles';

Promise.all([
    ...,
    ...,
    bundlesReadyPromise,
]).then(startApplication);

bundleReady();
```

In another bundle we just have to call `bundleReady`.

```js
// MediaBundle
import {bundleReady} from 'sulu-admin-bundle/services';

bundleReady();
```
