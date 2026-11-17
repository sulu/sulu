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
    valid={false}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
    rightButton={rightButton}
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
    disabled={true}
    emptyText="Nothing was selected!"
    leftButton={leftButton}
    onRemove={handleRemove}
    rightButton={rightButton}
>
    Test item
</SingleItemSelection>
```

The `loading` prop shows a loading state, `itemDisabled` disables the item and `allowRemoveWhileItemDisabled` keeps
the remove button usable then. `id` and `value` are passed to `onItemClick` when the item is clicked.

```javascript
const leftButton = {icon: 'su-document', onClick: () => {}};

<div>
    <SingleItemSelection leftButton={leftButton} loading={true} />
    <SingleItemSelection
        allowRemoveWhileItemDisabled={true}
        itemDisabled={true}
        leftButton={leftButton}
        onRemove={() => alert('Removed')}
    >
        Disabled item
    </SingleItemSelection>
    <SingleItemSelection
        id={5}
        leftButton={leftButton}
        onItemClick={(id, value) => alert('Clicked ' + id + ' with value ' + value)}
        value="my-value"
    >
        Clickable item
    </SingleItemSelection>
</div>
```
