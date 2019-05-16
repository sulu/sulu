The `Initializer` is used to initialize the application and sends two requests:
* Translations request
* Config request

When both requests are finished and processed the `initialized` boolean is set to true.

To use the config response for your bundle you can hook into this process by adding a `updateConfigHook`

```javascript static
import initializer from './services/Initializer';

initializer.addUpdateConfigHook('example_event', (config: Object, initialized: boolean) => {
    ...
});
```
