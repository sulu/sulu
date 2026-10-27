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
    buttons={[rightButton]}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
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
    buttons={[rightButton]}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
>
    Test item
</SingleItemSelection>
```

Further buttons on the right side can be passed with the `buttons` prop; they are rendered in order.

```javascript
const leftButton = {
    icon: 'su-document',
};

const buttons = [
    {
        icon: 'su-crop-pen',
        onClick: () => {
            alert('Crop was pressed!');
        },
    },
];

<SingleItemSelection buttons={buttons} leftButton={leftButton}>Test item</SingleItemSelection>
```

It will also show a defined message in a slightly greyed out tone if no item seems to be selected.

```javascript
const leftButton = {
    icon: 'su-document',
};

<SingleItemSelection emptyText="Nothing was selected!" leftButton={leftButton} />
```

If the component is not valid, it will have a red border.

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
    buttons={[rightButton]}
    valid={false}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
>
    Test item
</SingleItemSelection>
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
    buttons={[rightButton]}
    disabled={true}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
>
    Test item
</SingleItemSelection>
```
