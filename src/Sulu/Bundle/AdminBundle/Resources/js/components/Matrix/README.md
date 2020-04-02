Use this component to display a value matrix.

```javascript
const [value, setValue] = React.useState({
    'global.articles': {
        'view': true,
        'edit': true,
        'delete': false,
    },
    'global.redirects': {
        'view': true,
    },
    'global.settings': {
        'view': true,
        'edit': false,
    },
});

const onChange = (value) => setValue(value);

const Row = Matrix.Row;
const Item = Matrix.Item;

<Matrix title="Global" onChange={onChange} values={value}>
    <Row name="global.articles">
        <Item name="view" icon="su-pen" />
        <Item name="edit" icon="su-plus" />
        <Item name="delete" icon="su-trash-alt" />
    </Row>
    <Row name="global.redirects">
        <Item name="view" icon="su-pen" />
    </Row>
    <Row name="global.settings">
        <Item name="view" icon="su-pen" />
        <Item name="edit" icon="su-plus" />
    </Row>
</Matrix>
```
