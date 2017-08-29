```
const Draft = require('./../../components/Draft/Draft').default;
const textEditorRegistry = require('./registries/TextEditorRegistry').default;

if (!textEditorRegistry.has('draft')) {
    textEditorRegistry.add('draft', Draft);
}

const onChange = (value) => console.log(value);
<TextEditor onChange={onChange} />
```
