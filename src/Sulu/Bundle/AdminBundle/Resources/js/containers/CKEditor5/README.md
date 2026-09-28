This component uses the [CKEditor 5](https://ckeditor.com/ckeditor-5/) to display a text editor. Our component offers a
`value` prop to set the value. There is also an `onChange` callback called when a value changes and a `onBlur` callback
which is called when the editor loses the focus.

```javascript
const [value, setValue] = React.useState('');

const handleChange = (newValue) => setValue(newValue);
const handleBlur = () => alert('Text editing finished!');

<div>
    <CKEditor5 onBlur={this.handleBlur} onChange={handleChange} value={value} />

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

## Text part language

The editor ships the [`TextPartLanguage`](https://ckeditor.com/docs/ckeditor5/latest/features/language.html) feature,
which lets an editor mark a selection with a language (`<span lang="…" dir="…">`) to satisfy the WCAG "language of
parts" requirement. Marked text is highlighted in the editor and shows a tooltip with the language name; the saved
content only contains the `lang` and `dir` attributes.

The offered languages default to the languages of all webspace localizations, without their country variants. Their titles are localised to the
administration interface language via `Intl.DisplayNames`. Configure a different list in
`config/packages/sulu_admin.yaml`:

```yaml
sulu_admin:
    ckeditor:
        text_part_languages: ['en', 'de', 'ar']
```

Only language codes without a country (`de`, not `de_at`) are accepted.
