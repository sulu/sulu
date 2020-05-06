A block allows to group certain content in a collapsable container. Any children being passed to this component will be
displayed in it. The component takes an `onCollapse` and an `onExpand` callback, which will be called when the user
wants to trigger the corresponding action. In case you want to show a special handle, e.g. for drag and drop, then you
can use the `dragHandle` property to pass JSX.

```javascript
const Icon = require('../Icon').default;

const [expanded, setExpanded] = React.useState(true);

const onCollapse = () => setExpanded(false);
const onExpand = () => setExpanded(true);

<Block expanded={expanded} onCollapse={onCollapse} onExpand={onExpand} dragHandle={<Icon name="su-more" />}>
    That is the content of the block!
</Block>
```

When the `onRemove` callback is passed, there will also be a remove icon shown, which calls this callback when being
clicked. The `onSettingsClick` callback behaves the same but shows a settings icon.

```javascript
const [expanded, setExpanded] = React.useState(true);

const onCollapse = () => setExpanded(false);
const onSettingsClick = () => alert('Settings callback was invoked!');
const onExpand = () => setExpanded(true);
const onRemove = () => alert('Remove callback was invoked!');

<Block
    expanded={expanded}
    onSettingsClick={onSettingsClick}
    onCollapse={onCollapse}
    onExpand={onExpand}
    onRemove={onRemove}
>
    That is the content of the block!
</Block>
```

It is also possible to pass an object containing the available types, whereby the object key is the identifier and the
value a title. The values of this `types` prop will be used to render a select if there is more than one type
available. The select will call the passed callback prop named `onTypeChange`. The `activeType` prop should equal the
currently selected type.

```javascript
const [expanded, setExpanded] = React.useState(true);
const [activeType, setActiveType] = React.useState('type1');

const onCollapse = () => setExpanded(false);
const onExpand = () => setExpanded(true);
const onTypeChange = (type) => setActiveType(type);
const types = {
    type1: 'Type 1',
    type2: 'Type 2',
};

<Block
    activeType={activeType}
    expanded={expanded}
    onCollapse={onCollapse}
    onExpand={onExpand}
    onTypeChange={onTypeChange}
    types={types}
>
    That is a {activeType} Block!
</Block>
```
