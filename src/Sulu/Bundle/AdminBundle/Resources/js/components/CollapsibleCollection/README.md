The collapsible collection renders a set of `Collapsible` components, owns their expanded state and
adds a control to collapse or expand all of them at once. Every child starts expanded.

Each child should be given a stable `key`: the collection tracks a child's collapsed state by its
React key, so that state stays attached to the right card when children are added, removed or
reordered. A child without a key falls back to its position, which misattributes collapsed state
once children are removed from the middle of the list or reordered.

```javascript
const Collapsible = require('../Collapsible').default;

<CollapsibleCollection>
    <Collapsible key="general" subtitle="3 attributes" title="General">
        That is the content of the first collapsible!
    </Collapsible>
    <Collapsible key="marketing" subtitle="2 attributes" title="Marketing">
        That is the content of the second collapsible!
    </Collapsible>
</CollapsibleCollection>
```

Passing an `onAddClick` callback renders an add button below the collapsibles. The labels of the add
button and of the collapse and expand controls can be replaced with `addButtonText`,
`collapseAllText` and `expandAllText`.

```javascript
const Collapsible = require('../Collapsible').default;

const onAddClick = () => alert('Add callback was invoked!');

<CollapsibleCollection
    addButtonText="Add attributes"
    collapseAllText="Collapse all groups"
    expandAllText="Expand all groups"
    onAddClick={onAddClick}
>
    <Collapsible key="general" subtitle="3 attributes" title="General">
        That is the content of the first collapsible!
    </Collapsible>
    <Collapsible key="marketing" subtitle="2 attributes" title="Marketing">
        That is the content of the second collapsible!
    </Collapsible>
</CollapsibleCollection>
```
