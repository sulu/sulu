This container component offers a `textEditorRegistry`, which can be used to register text editors using a unique key.
There is also a `TextEditor` component which takes all the options passed to a `TextEditor` and an `adapter` prop, which
decides which `TextEditor` should be used.

```javascript
const CKEditor5 = require('../CKEditor5').default;

const [value, setValue] = React.useState('');

const textEditorRegistry = require('./registries/textEditorRegistry').default;
textEditorRegistry.clear();
textEditorRegistry.add('ckeditor5', CKEditor5);

const textEditorConfigRegistry = require('./registries/textEditorConfigRegistry').default;
textEditorConfigRegistry.clear();
textEditorConfigRegistry.add('default', {enterMode: 'p', features: ['align'], tags: ['strong', 'em', 'a']});

const handleBlur = () => alert('Text editing finished!');

<div>
    <TextEditor adapter="ckeditor5" onBlur={handleBlur} onChange={setValue} value={value} />

    Output: <pre>{value}</pre>
</div>
```

## Text editor configs

A text editor config describes which HTML the editor may produce. It deliberately contains nothing specific to a
concrete editor implementation, so that the same config can drive a different editor later:

* `tags`: the HTML tags the editor may produce, e.g. `a`, `strong`, `h2`, `table`. Only tags a plugin can switch
  off appear here; `p` and `br` are always available and are controlled by `enterMode`, not by `tags`.
* `features`: capabilities that are not an HTML tag, which is where attributes and inline styles are configured, e.g.
  `align`, which writes a `text-align` style on an existing element, or `lang`, which marks the language of a text
  part with a `lang` attribute.
* `enterMode`: whether the editor produces paragraphs (`p`) or line breaks (`br`).

The configs are defined in the Symfony configuration and delivered to the administration interface with the rest of the
application config, which fills the `textEditorConfigRegistry`:

```yaml
sulu_admin:
    text_editor:
        configs:
            default:
                tags:
                    table: false # switches a tag off, everything else Sulu ships stays enabled
            mini:
                enter_mode: br
                tags:
                    a: true
                    strong: true
                    em: true
```

A project block for a config Sulu ships (`default`, `mini`) is merged into it, so a single tag can be switched off
without restating the whole list. Any other name defines a new config from scratch.

A property picks its config with the `config` param, and falls back to `default`:

```xml
<property name="teaser" type="text_editor">
    <params>
        <param name="config" value="mini"/>
    </params>
</property>
```

The `formats` and `enter_mode` params of a `text_editor` property are deprecated. They still work and still override
the config, but they are removed in 4.0. Use a text editor config instead.

Narrowing a config for a field that already holds content is not free: the editor loads no plugin for a disabled tag,
and CKEditor drops markup it has no plugin for. A field that switches from a config with `table` to one without keeps
the stored HTML until the editor is touched, and saves the reduced markup from then on.
