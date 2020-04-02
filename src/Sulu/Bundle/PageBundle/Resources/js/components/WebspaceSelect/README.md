The `WebspaceSelect` component is used in the `WebspaceOverview` view, and it's based on the `ArrowMenu` component.

```javascript
const [value, setValue] = React.useState('sulu');

const handleWebspaceChange = (value) => {
    setValue(value);
};

<WebspaceSelect value={value} onChange={handleWebspaceChange}>
    <WebspaceSelect.Item value="sulu">
        Sulu
    </WebspaceSelect.Item>
    <WebspaceSelect.Item value="sulu_blog">
        Sulu Blog
    </WebspaceSelect.Item>
    <WebspaceSelect.Item value="sulu_doc">
        Sulu Doc
    </WebspaceSelect.Item>
</WebspaceSelect>
```
