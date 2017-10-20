The `ColumnList` component consists out of three parts: `ColumnList`, `Column` and `Item`. 
All of them has to be imported in order to build a table.

```
const Column = ColumnList.Column;
const Item = ColumnList.Item;

const buttons = [
    {
        icon: 'heart',
        onClick: (rowId) => {
            state.rows[rowId] = state.rows[rowId].map((cell) => 'You are awesome 😘');
            const newRows = state.rows;
    
            setState({
                rows: newRows,
            })
        }, 
    },
    {
        icon: 'pencil',
        onClick: (rowId) => {
            state.rows[rowId] = state.rows[rowId].map((cell) => 'You are awesome 😘');
            const newRows = state.rows;
    
            setState({
                rows: newRows,
            })
        }, 
    },
];

<ColumnList buttons={buttons}>
    <Column>
        <Item selected="true">Item 1</Item>
        <Item hasChildren="true">Item 1</Item>
        <Item>Item 1</Item>
    </Column>
    <Column>
        <Item>Item 1</Item>
        <Item hasChildren="true">Item 1</Item>
    </Column>
    <Column>
        <Item>Item 1</Item>
        <Item>Item 1</Item>
    </Column>
</ColumnList>
```
