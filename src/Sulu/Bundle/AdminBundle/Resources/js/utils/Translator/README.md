The `Translator` module is responsible for handling key-value pairs of translations. It can only handle one language at
a time - this means that the correct language has to be set using the `setTranslations` method. The `translate` method
does a simple lookup in the translation map and returns the value for the given key. If the key doesn't exist in the
map the key itself is returned, and a warning is logged.

```javascript static
import {setTranslations, translate} from './Translator';
setTranslations({
    'title': 'Title',
});

translate('title');    // returns Title
translate('test');     // returns test and logs a warning 
```
