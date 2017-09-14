The job of the Bundles service is to offer methods to guarantee that all index JavaScript files of every bundle
are already loaded before the application gets initialized.

When all bundle JavaScript files are loaded the `bundlesReadyPromise` gets resolved and that is where the
application gets started. We also call `bundleReady` to signalize that the AdminBundle is also ready.

```javascript static
import {bundlesReadyPromise, bundleReady} from './services/Bundles';

Promise.all([
    ...,
    ...,
    bundlesReadyPromise,
]).then(startApplication);

bundleReady();
```

In another bundle we just have to call `bundleReady` at the very end of the index file.

```javascript static
// MediaBundle
import {bundleReady} from 'sulu-admin-bundle/services';

bundleReady();
```
