The `ColumnList` component consists out of three parts: `ColumnList`, `Column` and `Item`. 
All of them has to be imported in order to build a table.

```
const Column = ColumnList.Column;
const Item = ColumnList.Item;

const buttons = [
    {
        icon: 'heart',
        onClick: (id) => {
            alert('Clicked heart button for item with id: ' + id);
        }, 
    },
    {
        icon: 'pencil',
        onClick: (id) => {
            alert('Clicked pencil button for item with id: ' + id);
        }, 
    },
];

const handleOnItemClick = (id) => {
    alert('Item with id: ' + id + ' clicked');
};

<ColumnList buttons={buttons} onItemClick={handleOnItemClick}>
    <Column>
        <Item id="1" selected="true">Item 1</Item>
        <Item id="2" hasChildren="true">Item 1</Item>
        <Item id="3">Item 1</Item>
    </Column>
    <Column>
        <Item id="1-1">Item 1</Item>
        <Item id="1-2" hasChildren="true">Item 1</Item>
    </Column>
    <Column>
        <Item id="1-1-1">Item 1</Item>
        <Item id="1-1-2">Item 1</Item>
    </Column>
</ColumnList>
```
