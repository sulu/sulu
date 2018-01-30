A block allows to group certain content in a collapsable container. Any children being passed to this component will be
displayed in it. The component takes an `onCollapse` and an `onExpand` callback, which will be called when the user
wants to trigger the corresponding action.

```javascript
initialState = {expanded: true};

const onCollapse = () => setState({expanded: false});
const onExpand = () => setState({expanded: true});

<Block expanded={state.expanded} onCollapse={onCollapse} onExpand={onExpand}>
    That is the content of the block!
</Block>
```
