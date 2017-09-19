```
const Item = ItemSelection.Item;

<ItemSelection
    label="Select an item"
    onItemRemove={(itemId) => {console.log('onItemRemove', itemId)}}
    leftButton={{
        icon: 'plus',
        onClick: () =>{console.log('leftButton')},
    }}
    rightButton={{
        icon: 'comment',
        onClick: () =>{console.log('rightButton')},
    }}
    onItemMove={(itemIds) => {console.log('onItemMove')}}
>
    <Item id="1">
        <div>
            I am an item. Hihi :)
        </div>
    </Item>
    <Item  id="2">
        <div>
            I am an item. Hihi :)
        </div>
    </Item>
    <Item  id="3">
        <div>
            I am an item. Hihi :)
        </div>
    </Item>
</ItemSelection>
```
