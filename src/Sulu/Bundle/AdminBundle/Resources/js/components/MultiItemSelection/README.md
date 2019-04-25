The `MultiItemSelection` component is a list used for referencing different datasets in `Sulu`. Inside the 
`MultiItemSelection` those references can be added and sorted. The sorting can be done by drag and drop.

```javascript
const arrayMove = require('sulu-admin-bundle/components').arrayMove;
const Item = MultiItemSelection.Item;

const items = [
    {
        id: 1,
        content: 'I am an item. Hihi :)'
    },
    {
        id: 2,
        content: 'Lorem ipsum doloris sit amet'
    },
    {
        id: 3,
        content: 'Thomas the little locomotive'
    },
];

initialState = {
    items: items,
};

const handleItemsSorted = (oldItemIndex, newItemIndex) => {
    setState({
        items: arrayMove(state.items, oldItemIndex, newItemIndex),
    });
};

const handleAddItem = () => {
    state.items.push({
        id: Date.now(),
        content: 'I was added :D'
    }),

    setState({
        items: state.items,
    });
};

<MultiItemSelection
    label="Select an item"
    leftButton={{
        icon: 'fa-plus',
        onClick: handleAddItem,
    }}
    onItemsSorted={handleItemsSorted}
>
    {state.items.map((item, index) =>
        <Item
            key={item.id}
            id={item.id}
            index={index + 1}
        >
            <div style={{height: '40px', lineHeight: '40px'}}>
                {item.content}
            </div>
        </Item>
    )}
</MultiItemSelection>
```

If the `onItemEdit` and `onItemRemove` callbacks are passed, the items can also be removed and edited, which calls the
corresponding callback.

```javascript
const arrayMove = require('sulu-admin-bundle/components').arrayMove;
const Item = MultiItemSelection.Item;

const items = [
    {
        id: 1,
        content: 'I am an item. Hihi :)'
    },
    {
        id: 2,
        content: 'Lorem ipsum doloris sit amet'
    },
    {
        id: 3,
        content: 'Thomas the little locomotive'
    },
];

initialState = {
    items: items,
};

const handleItemsSorted = (oldItemIndex, newItemIndex) => {
    setState({
        items: arrayMove(state.items, oldItemIndex, newItemIndex),
    });
};

const handleEdit = (itemId) => {
    alert('Do whatever needs to be done to edit the item with the ID ' + itemId);
};

const handleRemove = (itemId) => {
    setState({
        items: state.items.filter((item) => item.id !== itemId),
    });
};

const handleAddItem = () => {
    state.items.push({
        id: Date.now(),
        content: 'I was added :D'
    }),

    setState({
        items: state.items,
    });
};

<MultiItemSelection
    label="Select an item"
    leftButton={{
        icon: 'fa-plus',
        onClick: handleAddItem,
    }}
    onItemEdit={handleEdit}
    onItemsSorted={handleItemsSorted}
    onItemRemove={handleRemove}
>
    {state.items.map((item, index) =>
        <Item
            key={item.id}
            id={item.id}
            index={index + 1}
        >
            <div style={{height: '40px', lineHeight: '40px'}}>
                {item.content}
            </div>
        </Item>
    )}
</MultiItemSelection>
```
