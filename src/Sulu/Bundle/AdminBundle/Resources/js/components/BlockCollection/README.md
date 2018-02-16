This component allows to have multiple [`Block`](#block) components in a collection. These blocks can also be reordered
in different ways.

The `value` this component is passed has to be an array. For each entry in this array a `Block` is rendered. The
rendering of these `Blocks` can be affected by the passed `renderBlockContent` callback. This callback retrieves the
`value` for this specific block as argument, and should return the rendered JSX.

```javascript
initialState = {value: [{content: 'That is some content'}, {content: 'That is some more content'}]};

const onChange = (value) => {
    setState({value});
};

const renderBlockContent = (value) => (<p>{value.content || <i>There is no content</i>}</p>);

<BlockCollection onChange={onChange} value={state.value} renderBlockContent={renderBlockContent} />
```

By passing the `types` argument to the block it is possible to allow every single `Block` to be chosen a specific type.
This can also have an impact on the rendering of the block. This is enabled by having a `type` passed as second
argument to the `renderBlockContent` callback if types are available.

```javascript
initialState = {value: []};

const onChange = (value) => {
    setState({value});
};

const types = {
    type1: 'Type 1',
    type2: 'Type 2',
};

const renderBlockContent = (value, type) => 'This block does not really care about its value... But about its type, which is ' + type;

<BlockCollection
    onChange={onChange}
    renderBlockContent={renderBlockContent}
    types={types}
    value={state.value}
/>
```

It is also possible to set the minimum and maximum amount of blocks on the `BlockCollection`, so it does not allow to
have more or less blocks.

```javascript
initialState = {value: []};

const onChange = (value) => {
    setState({value});
};

const renderBlockContent = (value) => 'A not so unique block';

<BlockCollection
    maxOccurs={5}
    minOccurs={2}
    onChange={onChange}
    renderBlockContent={renderBlockContent}
    value={state.value}
/>
```
