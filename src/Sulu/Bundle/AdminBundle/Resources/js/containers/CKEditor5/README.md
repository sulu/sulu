This component uses the [CKEditor 5](https://ckeditor.com/ckeditor-5/) to display a text editor. Our component offers a
`value` prop to set the value. There is also an `onChange` callback called when a value changes and a `onBlur` callback
which is called when the editor loses the focus. The `config` prop holds the resolved text editor config, which decides
which plugins are loaded.

```javascript
const {registerCKEditor5Plugins} = require('./index');
registerCKEditor5Plugins();

const [value, setValue] = React.useState('');

const handleChange = (newValue) => setValue(newValue);
const handleBlur = () => alert('Text editing finished!');

<div>
    <CKEditor5
        config={{enterMode: 'p', features: [], tags: ['strong', 'i']}}
        onBlur={handleBlur}
        onChange={handleChange}
        value={value}
    />

    Output: <pre>{value}</pre>
</div>
```

The editor can be extended by adding more plugins and more configuration. That's what the `PluginRegistry` and
`ConfigRegistry` are for:

```javascript static
import {ckeditorPluginRegistry, ckeditorConfigRegistry} from 'sulu-admin-bundle/containers';
import {Font} from '@ckeditor/ckeditor5-font';

ckeditorPluginRegistry.add(Font);
ckeditorConfigRegistry.add((config) => ({
    toolbar: [...config.toolbar, 'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'],
}));
```

The `PluginRegistry` has an `add` method which takes the plugin class for the CKEditor. Please be aware that the
plugin must be compatible with the ckeditor version used in your project (which can be outputted by executing
`npm list @ckeditor/ckeditor5-core`).
The `ConfigRegistry` takes a function, which receives the config which is already there. The return value of this 
function will be shallow merged with the previously existing config. You can reuse the old values from the config, 
as seen e.g. in the above code snippet.

## Binding a plugin to a tag or feature

Both registries take an optional second argument: the tag or feature the registration belongs to. Sulu then only loads
that plugin and applies that config if the text editor config of the edited property enables the given key. A single
registration can be bound to several keys, and is loaded as soon as one of them is enabled. A registration without a
key is applied to every text editor config, which is why the example above keeps working unchanged.

```javascript static
import {ckeditorPluginRegistry, ckeditorConfigRegistry} from 'sulu-admin-bundle/containers';
import {Table, TableToolbar} from '@ckeditor/ckeditor5-table';

ckeditorPluginRegistry.add(Table, 'table');
ckeditorPluginRegistry.add(TableToolbar, 'table');
ckeditorConfigRegistry.add((config) => ({
    toolbar: [...config.toolbar, 'insertTable'],
}), 'table');
```

A key names the plugin, not always the element it produces: `i` is CKEditor's Italic, which also reads `<em>`, and
`table` renders a `<figure class="table">` around the table.

A key does not have to be a tag. Anything a plugin adds that is not an element of its own is a feature, which is how
attributes and inline styles are configured. `align` writes a `text-align` style on an existing element, and `lang`
writes a `lang` attribute on a `span`:

```javascript static
import {ckeditorPluginRegistry, ckeditorConfigRegistry} from 'sulu-admin-bundle/containers';
import {TextPartLanguage} from '@ckeditor/ckeditor5-language';

ckeditorPluginRegistry.add(TextPartLanguage, 'lang');
ckeditorConfigRegistry.add((config) => ({
    toolbar: [...config.toolbar, 'textPartLanguage'],
}), 'lang');
```

A project enables it with `features: {lang: true}`. Sulu registers this plugin but enables it in no shipped config,
because marking the language of a text part produces markup the editor could not produce before.

The tags and features themselves are configured in the Symfony configuration, see the `TextEditor` container. A key
that is enabled there but has neither a plugin nor a config registered for it is reported with a warning in the
browser console.

The `ConfigRegistry` takes a priority as its third argument. Configs with a higher priority are applied first, and
since a config usually appends to `config.toolbar`, a higher priority places the toolbar item further to the left. The
default priority is `0`, so anything a project registers ends up behind the items Sulu ships. The config function also
receives the resolved text editor config as its second argument, which is how the heading options are built from the
enabled `h*` tags.
