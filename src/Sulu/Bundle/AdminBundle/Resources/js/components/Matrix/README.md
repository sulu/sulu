Use this component to display a value matrix.

```javascript
initialState = {
    values: {
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
    }
};

const handleChange = (newValues) => {
    setState({values: newValues});
};

const Row = Matrix.Row;
const Item = Matrix.Item;


<Matrix title="Global" onChange={handleChange} values={state.values}>
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
