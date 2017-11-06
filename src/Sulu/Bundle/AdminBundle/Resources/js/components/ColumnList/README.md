The `ColumnList` component consists out of three parts: `ColumnList`, `Column` and `Item`. 

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

const toolbarItems = [
    {
        icon: 'plus',
        type: 'simple',
        onClick: (index) => {
            alert('Clicked plus button for item with index: ' + index);
        },
    },
    {
        icon: 'search',
        type: 'simple',
        skin: 'secondary',
        onClick: (index) => {
            alert('Clicked search button for column with index: ' + index);
        },
    },
    {
        icon: 'gear',
        type: 'dropdown',
        options: [
            {
                label: 'Option1 ',
                onClick: (index) => {
                    alert('Clicked option1 for column with index: ' + index);
                },
            },
            {
                label: 'Option2 ',
                onClick: (index) => {
                    alert('Clicked option2 for column with index: ' + index);
                },
            },
        ],
    },
];

<div style={{height: '60vh'}}>
    <ColumnList buttons={buttons} onItemClick={handleOnItemClick} toolbarItems={toolbarItems}>
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
</div>
```
