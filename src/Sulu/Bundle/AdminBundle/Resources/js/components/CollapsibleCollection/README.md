The collapsible collection renders a `Collapsible` for every entry of its `value` array and owns
their expanded state. Every entry needs a `title` and may bring a `subtitle`, the content of a
collapsible comes from the `renderCollapsibleContent` callback. As soon as there is more than one
entry, a control to collapse or expand all of them at once is shown. Every collapsible starts
expanded.

The collapsibles are sorted by dragging their handle, which reorders `value` and reports the new
order through `onChange`.

```javascript
const [value, setValue] = React.useState([
    {subtitle: '3 attributes', title: 'General'},
    {subtitle: '2 attributes', title: 'Marketing'},
]);

const renderCollapsibleContent = (collapsible) => (
    <div>That is the content of the {collapsible.title} collapsible!</div>
);

<CollapsibleCollection
    onChange={setValue}
    renderCollapsibleContent={renderCollapsibleContent}
    value={value}
/>
```

Set `movable` to `false` to render the collapsibles without a drag handle. The `actions` are shown
as icon buttons in the header of every collapsible, and their `onClick` is called with the index of
the collapsible it was invoked on.

```javascript
const [value, setValue] = React.useState([
    {subtitle: '3 attributes', title: 'General'},
    {subtitle: '2 attributes', title: 'Marketing'},
]);

const actions = [
    {icon: 'su-trash-alt', label: 'Delete', onClick: (index) => alert('Delete was invoked on ' + index)},
];

const renderCollapsibleContent = (collapsible) => (
    <div>That is the content of the {collapsible.title} collapsible!</div>
);

<CollapsibleCollection
    actions={actions}
    movable={false}
    onChange={setValue}
    renderCollapsibleContent={renderCollapsibleContent}
    value={value}
/>
```

Passing an `onAddClick` callback renders an add button below the collapsibles. The labels of the add
button and of the control collapsing and expanding all collapsibles at once can be replaced with
`addButtonText`, `collapseAllText` and `expandAllText`.

```javascript
const [value, setValue] = React.useState([
    {subtitle: '3 attributes', title: 'General'},
    {subtitle: '2 attributes', title: 'Marketing'},
]);

const renderCollapsibleContent = (collapsible) => (
    <div>That is the content of the {collapsible.title} collapsible!</div>
);

<CollapsibleCollection
    addButtonText="Add attributes"
    collapseAllText="Collapse all groups"
    expandAllText="Expand all groups"
    onAddClick={() => alert('Add callback was invoked!')}
    onChange={setValue}
    renderCollapsibleContent={renderCollapsibleContent}
    value={value}
/>
```
