A collapsible groups content in a card with a header. The header shows a `title`, an optional grey
`subtitle`, an optional `extra` node and any number of `actions`, each rendered as an icon
button. The component is controlled: pass `expanded` together with the `onExpand` and `onCollapse`
callbacks.

```javascript
const [expanded, setExpanded] = React.useState(true);

const onCollapse = () => setExpanded(false);
const onExpand = () => setExpanded(true);

<Collapsible
    expanded={expanded}
    onCollapse={onCollapse}
    onExpand={onExpand}
    subtitle="3 attributes"
    title="General"
>
    That is the content of the collapsible!
</Collapsible>
```

Actions are shown as icon buttons in the header. Their `label` becomes the button's accessible name,
and clicking one never expands or collapses the card.

```javascript
const [expanded, setExpanded] = React.useState(true);

const onCollapse = () => setExpanded(false);
const onExpand = () => setExpanded(true);
const actions = [
    {icon: 'su-trash-alt', label: 'Delete', onClick: () => alert('Delete was invoked!')},
];

<Collapsible
    actions={actions}
    expanded={expanded}
    onCollapse={onCollapse}
    onExpand={onExpand}
    title="General"
>
    That is the content of the collapsible!
</Collapsible>
```

The `extra` prop takes arbitrary JSX and is rendered in the header before the actions, which
makes it a good place for a progress indicator. The `handle` prop works the same way as on the
`Block` component and renders a separate column on the left, e.g. for a drag handle.

```javascript
const Icon = require('../Icon').default;

const [expanded, setExpanded] = React.useState(false);

const onCollapse = () => setExpanded(false);
const onExpand = () => setExpanded(true);

<Collapsible
    expanded={expanded}
    extra={<span>4/7</span>}
    handle={<Icon name="su-more" />}
    onCollapse={onCollapse}
    onExpand={onExpand}
    title="Electrical specifications"
>
    That is the content of the collapsible!
</Collapsible>
```

If the `onCollapse` and `onExpand` props are not set, the collapsible cannot be collapsed and no
toggle is rendered:

```javascript
<Collapsible title="General">
    That is the content of the collapsible!
</Collapsible>
```
