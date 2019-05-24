The `SingleItemSelection` component is a list used for referencing a single item in `Sulu`.

```javascript
const leftButton = {
    icon: 'su-document',
    onClick: () => {
        alert('Button pressed!');
    },
};

const rightButton = {
    icon: 'su-display-default',
    onClick: () => {
        alert('Right button pressed!');
    },
};

const handleRemove = () => {
    alert('Remove was pressed!');
};

<SingleItemSelection
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
    rightButton={rightButton}
>
    Test item
</SingleItemSelection>
```

It can also handle different options on its right button.

```javascript
const leftButton = {
    icon: 'su-document',
    onClick: () => {
        alert('Button pressed!');
    },
};

const rightButton = {
    icon: 'su-display-default',
    onClick: (value) => {
        alert(value + ' was pressed!');
    },
    options: [
        {
            label: 'Left',
            value: 'left',
        },
        {
            label: 'Right',
            value: 'right',
        },
    ],
};

const handleRemove = () => {
    alert('Remove was pressed!');
};

<SingleItemSelection
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
    rightButton={rightButton}
>
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

The component can also be rendered in a disabled state:

```javascript
const leftButton = {
    icon: 'su-document',
    onClick: () => {
        alert('Button pressed!');
    },
};

const rightButton = {
    icon: 'su-display-default',
    onClick: (value) => {
        alert(value + ' was pressed!');
    },
    options: [
        {
            label: 'Left',
            value: 'left',
        },
    ],
};

const handleRemove = () => {
    alert('Remove was pressed!');
};

<SingleItemSelection
    disabled={true}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
    rightButton={rightButton}
>
    Test item
</SingleItemSelection>
```
