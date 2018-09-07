The `MultiItemSelection` component is a list used for referencing different datasets in `Sulu`. Inside the 
`MultiItemSelection` those references can be added, sorted and deleted. The sorting can be done by drag and drop.

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
    onItemRemove={handleRemove}
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
            <div>
                {item.content}
            </div>
        </Item>
    )}
</MultiItemSelection>
```
