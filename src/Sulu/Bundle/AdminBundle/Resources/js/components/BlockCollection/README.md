This component allows to have multiple [`Block`](#block) components in a collection. These blocks can also be reordered
in different ways.

The `value` this component is passed has to be an array. For each entry in this array a `Block` is rendered. The
rendering of these `Blocks` can be affected by the passed `renderBlockContent` callback. This callback retrieves the
`value` for this specific block as argument, and should return the rendered JSX.

```javascript
const [value, setValue] = React.useState([{content: 'That is some content'}, {content: 'That is some more content'}]);

const renderBlockContent = (value) => (<p>{value.content || <i>There is no content</i>}</p>);

<BlockCollection onChange={setValue} value={value} renderBlockContent={renderBlockContent} />
```

By passing the `types` argument to the block it is possible to allow every single `Block` to be chosen a specific type.
This can also have an impact on the rendering of the block. This is enabled by having a `type` passed as second
argument to the `renderBlockContent` callback if types are available.

```javascript
const [value, setValue] = React.useState([]);

const types = {
    type1: 'Type 1',
    type2: 'Type 2',
};

const renderBlockContent = (value, type) => 'This block does not really care about its value... But about its type, which is ' + type;

<BlockCollection
    onChange={setValue}
    renderBlockContent={renderBlockContent}
    types={types}
    value={value}
/>
```

It is also possible to set the minimum and maximum amount of blocks on the `BlockCollection`, so it does not allow to
have more or less blocks.

```javascript
const [value, setValue] = React.useState([]);

const renderBlockContent = (value) => 'A not so unique block';

<BlockCollection
    maxOccurs={5}
    minOccurs={2}
    onChange={setValue}
    renderBlockContent={renderBlockContent}
    value={value}
/>
```

The `movable` flag can be used to disable the moving feature of blocks.

```javascript
const [value, setValue] = React.useState([{content: 'That is some content'}, {content: 'That is some more content'}]);

const renderBlockContent = (value) => (<p>{value.content || <i>There is no content</i>}</p>);

<BlockCollection movable={false} onChange={setValue} value={value} renderBlockContent={renderBlockContent} />
```

Setting the `collapsable` flag to false will cause all blocks to be expanded and there will be no possibility to
collapse them.

```javascript
const [value, setValue] = React.useState([{content: 'That is some content'}, {content: 'That is some more content'}]);

const renderBlockContent = (value) => (<p>{value.content || <i>There is no content</i>}</p>);

<BlockCollection collapsable={false} onChange={setValue} value={value} renderBlockContent={renderBlockContent} />
```
