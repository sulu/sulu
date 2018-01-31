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
