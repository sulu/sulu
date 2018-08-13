The `ColumnList` component consists out of three parts: `ColumnList`, `Column` and `Item`. The `toolbarItems` prop
can be used to configure the toolbar above every column. There is also a `indicators` prop on the `Item`, which
contains an array of JSX, that will be displayed on the right side of the item. `buttons` are also added on the `Item`,
because they can differ from `Item` to `Item`.

```
const Icon = require('../Icon').default;

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
            {
                isDisabled: (index) => true,
                label: 'Option3 ',
                onClick: (index) => {
                    alert('This alert will never be called, because the button is disabled...');
                },
            },
        ],
    },
];

const indicators = [
    <Icon name="fa-square" />,
    <Icon name="fa-square-o" />,
];

<div style={{height: '60vh'}}>
    <ColumnList buttons={buttons} onItemClick={handleItemClick} toolbarItems={toolbarItems}>
        <ColumnList.Column>
            <ColumnList.Item buttons={buttons} id="1">Google 1</ColumnList.Item>
            <ColumnList.Item buttons={buttons} id="2" hasChildren="true" disabled={true}>Apple 1</ColumnList.Item>
            <ColumnList.Item buttons={buttons} id="3">Microsoft 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item buttons={buttons} id="1-1" indicators={indicators}>Item 1</ColumnList.Item>
            <ColumnList.Item buttons={buttons} id="1-2" hasChildren="true" indicators={indicators}>Item 1</ColumnList.Item>
        </ColumnList.Column>
        <ColumnList.Column>
            <ColumnList.Item buttons={buttons} id="1-1-1">Item 1</ColumnList.Item>
            <ColumnList.Item buttons={buttons} id="1-1-2">Item 1</ColumnList.Item>
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
            <ColumnList.Item id="1">Google 1</ColumnList.Item>
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
