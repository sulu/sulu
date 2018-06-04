The `Initializer` is used to initialize the application.
Method `initiliaze` waits for the [`bundleReadyPromise`](../Bundles/README.md) and sends then two requests:
* Translations request
* Config request

When both requests are finished and processed the `initialized` boolean is set to true.