The `Initializer` is used to initialize the application.
Method `initiliaze` waits for the [`bundleReadyPromise`](../Bundles/README.md) and sends then two requests:
* Translations request
* Config request

When the translation request is successfully loaded and processed the `translationInitialized` boolean is set to true.
When the config request is successfully loaded and processed the `initialized` boolean is set to true.