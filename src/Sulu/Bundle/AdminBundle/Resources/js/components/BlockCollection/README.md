This component allows to have multiple [`Block`](#block) components in a collection. These blocks can also be reordered
in different ways.

```javascript
initialState = {value: []};

const onChange = (value) => {
    setState({value});
};

<BlockCollection onChange={onChange} value={state.value} />
```
