The `SingleItemSelection` component is a list used for referencing a single item in `Sulu`.

```javascript
const leftButton = {
    icon: 'su-document',
    onClick: () => {
        alert('Button pressed!');
    },
};

const handleRemove = () => {
    alert('Remove was pressed!');
};

<SingleItemSelection emptyText="Nothing was selected!" leftButton={leftButton} onRemove={handleRemove}>
    Test item
</SingleItemSelection>
```

It will also show a defined message in a slightly greyed out tone if no item seems to be selected.

```javascript
const leftButton = {
    icon: 'su-document',
};

<SingleItemSelection emptyText="Nothing was selected!" leftButton={leftButton} />
```
