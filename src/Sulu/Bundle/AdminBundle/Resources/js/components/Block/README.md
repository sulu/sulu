A block allows to group certain content in a collapsable container. Any children being passed to this component will be
displayed in it. The component takes an `onCollapse` and an `onExpand` callback, which will be called when the user
wants to trigger the corresponding action. In case you want to show a special handle, e.g. for drag and drop, then you
can use the `dragHandle` property to pass JSX.

```javascript
const Icon = require('../Icon').default;

initialState = {expanded: true};

const onCollapse = () => setState({expanded: false});
const onExpand = () => setState({expanded: true});

<Block expanded={state.expanded} onCollapse={onCollapse} onExpand={onExpand} dragHandle={<Icon name="su-more" />}>
    That is the content of the block!
</Block>
```

When the `onRemove` callback is passed, there will also be a remove icon shown, which calls this callback when being
clicked.

```javascript
initialState = {expanded: true};

const onCollapse = () => setState({expanded: false});
const onExpand = () => setState({expanded: true});
const onRemove = () => alert('Remove callback was invoked!');

<Block expanded={state.expanded} onCollapse={onCollapse} onExpand={onExpand} onRemove={onRemove}>
    That is the content of the block!
</Block>
```

It is also possible to pass an object containing the available types, whereby the object key is the identifier and the
value a title. The values of this `types` prop will be used to render a select if there is more than one type
available. The select will call the passed callback prop named `onTypeChange`. The `activeType` prop should equal the
currently selected type.

```javascript
initialState = {activeType: 'type2', expanded: true};

const onCollapse = () => setState({expanded: false});
const onExpand = () => setState({expanded: true});
const onTypeChange = (type) => setState({activeType: type});
const types = {
    type1: 'Type 1',
    type2: 'Type 2',
};

<Block
    activeType={state.activeType}
    expanded={state.expanded}
    onCollapse={onCollapse}
    onExpand={onExpand}
    onTypeChange={onTypeChange}
    types={types}
>
    That is a {state.activeType} Block!
</Block>
```
