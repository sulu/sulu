This component uses the [CKEditor 5](https://ckeditor.com/ckeditor-5/) to display a text editor. Our component offers a
`value` prop to set the value. There is also an `onChange` callback called when a value changes and a `onBlur` callback
which is called when the editor loses the focus.

```javascript
initialState = {
    value: '',
}

const handleChange = (newValue) => setState({value: newValue});
const handleBlur = () => alert('Text editing finished!');

<div>
    <CKEditor5 onBlur={this.handleBlur} onChange={handleChange} />

    <p>
        Output: <pre>{state.value}</pre>
    </p>
</div>
```

The editor can be extended by adding more plugins and more configuration. That's what the `PluginRegistry` and
`ConfigRegistry` are for:

```javascript static
import {ckeditorPluginRegistry, ckeditorConfigRegistry} from 'sulu-admin-bundle/containers';
import Font from '@ckeditor/ckeditor5-font/src/font';

ckeditorPluginRegistry.add(Font);
ckeditorConfigRegistry.add((config) => ({
    toolbar: [...config.toolbar, 'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'],
}));
```

The `PluginRegistry` has an `add` method which takes the plugin class for the CKEditor, while the `ConfigRegistry`
takes a function, which receives the config which is already there. The return value of this function will be shallow
merged with the previously existing config. You can reuse the old values from the config, as seen e.g. in the above code
snippet.
