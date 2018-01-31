This component allows to have multiple [`Block`](#block) components in a collection. These blocks can also be reordered
in different ways.

The `value` this component is passed has to be an array. For each entry in this array a `Block` is rendered. The
rendering of these `Blocks` can be affected by the passed `renderBlockContent` callback. This callback retrieves the
`value` for this specific block as the only argument, and should return the rendered JSX.

```javascript
initialState = {value: [{content: 'That is some content'}, {content: 'That is some more content'}]};

const onChange = (value) => {
    setState({value});
};

const renderBlockContent = (value) => (<p>{value.content || <i>There is no content</i>}</p>);

<BlockCollection onChange={onChange} value={state.value} renderBlockContent={renderBlockContent} />
```

By passing the `types` argument to the block it is possible to allow every single `Block` to be chosen a specific type.
This can also have an impact on the rendering of the block.

```javascript
initialState = {value: []};

const onChange = (value) => {
    setState({value});
};

const types = {
    type1: 'Type 1',
    type2: 'Type 2',
};

const renderBlockContent = () => 'This block does not really care about its value...';

<BlockCollection
    onChange={onChange}
    renderBlockContent={renderBlockContent}
    types={types}
    value={state.value}
/>
```
