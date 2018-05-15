The `Initializer` is used to initialize the application.
Current there are two public methods to call.

### `registerDatagrid`

This method is used to register the datagrid adapters and field types.

### `initialize`

This method is used to load the translations and the config from the backend.
This method waits for the `bundleReadyPromise`.