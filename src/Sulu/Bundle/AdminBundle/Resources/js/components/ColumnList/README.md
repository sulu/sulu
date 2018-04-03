The `ColumnList` component consists out of three parts: `ColumnList`, `Column` and `Item`. The `toolbarItems` prop
can be used to configure the toolbar above every column.

```
const buttons = [
    {
        icon: 'fa-heart',
        onClick: (id) => {
            alert('Clicked heart button for item with id: ' + id);
        }, 
    },
    {
        icon: 'fa-pencil',
        onClick: (id) => {
            alert('Clicked pencil button for item with id: ' + id);
        }, 
    },
];

const handleItemClick = (id) => {
    alert('Item with id: ' + id + ' clicked');
};

const toolbarItems = [
    {
        icon: 'fa-plus',
        type: 'button',
        onClick: (index) => {
            alert('Clicked plus button for item with index: ' + index);
        },
    },
    {
        icon: 'fa-search',
        type: 'button',
        skin: 'secondary',
        onClick: (index) => {
            alert('Clicked search button for column with index: ' + index);
        },
    },
    {
        icon: 'fa-gear',
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
    <ColumnList buttons={buttons} onItemClick={handleItemClick} toolbarItems={toolbarItems}>
        <ColumnList.Column>
            <ColumnList.Item id="1" selected="true">Google 1</ColumnList.Item>
            <ColumnList.Item id="2" hasChildren="true">Apple 1</ColumnList.Item>
            <ColumnList.Item id="3">Microsoft 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item id="1-1">Item 1</ColumnList.Item>
            <ColumnList.Item id="1-2" hasChildren="true">Item 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item id="1-1-1">Item 1</ColumnList.Item>
            <ColumnList.Item id="1-1-2">Item 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column />
    </ColumnList>
</div>
```

The `toolbarItems` prop is optional, and the component can also be used without a toolbar.

```
<div style={{height: '60vh'}}>
    <ColumnList>
        <ColumnList.Column>
            <ColumnList.Item id="1" selected="true">Google 1</ColumnList.Item>
            <ColumnList.Item id="2" hasChildren="true">Apple 1</ColumnList.Item>
            <ColumnList.Item id="3">Microsoft 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item id="1-1">Item 1</ColumnList.Item>
            <ColumnList.Item id="1-2" hasChildren="true">Item 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item id="1-1-1">Item 1</ColumnList.Item>
            <ColumnList.Item id="1-1-2">Item 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column />
    </ColumnList>
</div>
```
