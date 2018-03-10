The `Table` component consists out of six parts: `Table`, `Header`, `Body`, `Row`, `Cell` and `HeaderCell`. 
All of them has to be imported in order to build a table.

Here is an example of a simple table. The structure of the `Table` component is almost identical to a normal HTML table.
The only difference can be seen in the header section where you have to omit the table row and add the `HeaderCell` 
components as direct children. This is due to the fact that the `Table` component expects only one row inside the its 
header.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

<Table>
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
        <HeaderCell>Column 5</HeaderCell>
        <HeaderCell>Column 6</HeaderCell>
        <HeaderCell>Column 7</HeaderCell>
        <HeaderCell>Column 8</HeaderCell>
        <HeaderCell>Column 9</HeaderCell>
    </Header>
    <Body>
        <Row>
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
            <Cell>Content 6</Cell>
            <Cell>Content 7</Cell>
            <Cell>Content 8</Cell>
            <Cell>Content 9</Cell>
        </Row>
        <Row>
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
            <Cell>Content 6</Cell>
            <Cell>Content 7</Cell>
            <Cell>Content 8</Cell>
            <Cell>Content 9</Cell>
        </Row>
        <Row>
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
            <Cell>Content 6</Cell>
            <Cell>Content 7</Cell>
            <Cell>Content 8</Cell>
            <Cell>Content 9</Cell>
        </Row>
        <Row>
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
            <Cell>Content 6</Cell>
            <Cell>Content 7</Cell>
            <Cell>Content 8</Cell>
            <Cell>Content 9</Cell>
        </Row>
    </Body>
</Table>
```

An empty table component will be rendered as follows. You can set a `placeholderText`.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

const buttons = [{
    icon: 'fa-heart',
    onClick: (rowId) => {
        state.rows[rowId] = state.rows[rowId].map((cell) => 'You are awesome 😘');
        const newRows = state.rows;

        setState({
            rows: newRows,
        })
    },
}];

<Table buttons={buttons} placeholderText="Awwww, this little fella has no entries...">
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
    </Header>
    <Body></Body>
</Table>
```

You can add buttons to the table which will always be prepended as the first cell in every row.
You can set an `onclick` handler and the icon name of a button by passing an array of objects to the `buttons` prop
like it is done in the example below. 
Hover the first cell and click on the button to change the boring text inside each row.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

initialState = {
    rows: [
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
    ],
};

const buttons = [{
    icon: 'fa-heart',
    onClick: (rowId) => {
        state.rows[rowId] = state.rows[rowId].map((cell) => 'You are awesome 😘');
        const newRows = state.rows;

        setState({
            rows: newRows,
        })
    },
}];

<Table buttons={buttons}>
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
        <HeaderCell>Column 5</HeaderCell>
    </Header>
    <Body>
        {
            state.rows.map((row, index) => {
                return (
                    <Row key={index}>
                        {
                            row.map((cell, index) => {
                                return (
                                    <Cell key={index}>{cell}</Cell>
                                )
                            })
                        }
                    </Row>
                )
            })
        }
    </Body>
</Table>
```

The rows inside the table can also be selected by setting the `selectMode` property on the table. The table 
distinguishes between `single` and `multiple` selection mode. The single selection mode prepends the `Radio` component
to each row as you can see in the following example.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

initialState = {
    rows: [1, 2, 3, 4, 5],
    selectedRowId: null,
};

function isSelected(rowId) {
    return rowId === state.selectedRowId;
}

const handleRowSelectionChange = (rowId) => {
    setState({
        selectedRowId: rowId,
    });
};

<Table selectMode="single" onRowSelectionChange={handleRowSelectionChange}>
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
    </Header>
    <Body>
        {
            state.rows.map((rowId, index) => {
                return (
                    <Row 
                        key={index}
                        id={rowId}
                        selected={isSelected(rowId)}>
                        <Cell>Content 1</Cell>
                        <Cell>Content 2</Cell>
                        <Cell>Content 3</Cell>
                        <Cell>Content 4</Cell>
                    </Row>
                )
            })
        }
    </Body>
</Table>
```

The multiple selection mode prepends the `Checkbox` component to each row and also a "select-all" checkbox to the
header.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

initialState = {
    rows: [1, 2, 3, 4, 5],
    selectedRowIds: [],
};

function isSelected(rowId) {
    return state.selectedRowIds.includes(rowId);
}

const handleRowSelectionChange = (rowId, checked) => {
    if (checked) {
        setState({
            selectedRowIds: [...state.selectedRowIds, rowId],
        });
    } else {
        setState({
            selectedRowIds: state.selectedRowIds.filter((selectedRowId) => selectedRowId !== rowId),
        });
    }
};

const handleAllSelectionChange = (allSelected) => {
    if (allSelected) {
        setState({
            selectedRowIds: [...state.rows],
        });
    } else {
        setState({
            selectedRowIds: [],
        });
    }
};

<Table 
    selectMode="multiple"
    onRowSelectionChange={handleRowSelectionChange}
    onAllSelectionChange={handleAllSelectionChange}>
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
        <HeaderCell>Column 5</HeaderCell>
        <HeaderCell>Column 6</HeaderCell>
        <HeaderCell>Column 7</HeaderCell>
        <HeaderCell>Column 8</HeaderCell>
        <HeaderCell>Column 9</HeaderCell>
    </Header>
    <Body>
        {
            state.rows.map((rowId, index) => {
                return (
                    <Row 
                        key={index}
                        id={rowId}
                        selected={isSelected(rowId)}>
                        <Cell>Content 1</Cell>
                        <Cell>Content 2</Cell>
                        <Cell>Content 3</Cell>
                        <Cell>Content 4</Cell>
                        <Cell>Content 5</Cell>
                        <Cell>Content 6</Cell>
                        <Cell>Content 7</Cell>
                        <Cell>Content 8</Cell>
                        <Cell>Content 9</Cell>
                    </Row>
                )
            })
        }
    </Body>
</Table>
```

The cells inside the table header can be made clickable by adding an `onClick` handler to the specific HeaderCell 
component. In addition with the `sortMode` property this can be used to change the sorting order of a column.
If the `sortMode` property is set, an indicator appears next to the header cell.

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

initialState = {
    rows: [
        [1, 'A', 'is Love, Content is Life!'],
        [2, 'B', 'is needed for a good table'],
        [3, 'C', 'of a really awesome cell'],
    ],
    sortMode: 'ascending',
};

const handleSortColumnA = () => {
    if (state.sortMode === 'ascending') {
        setState({
            rows: state.rows.sort((a, b) => a[0] < b[0]),
            sortMode: 'descending',
        });
    } else {
        setState({
            rows: state.rows.sort((a, b) => a[0] > b[0]),
            sortMode: 'ascending',
        });
    }
};

<Table>
    <Header>
        <HeaderCell onClick={handleSortColumnA} sortMode={state.sortMode}>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
    </Header>
    <Body>
        {
            state.rows.map((row, index) => {
                return (
                    <Row key={index}>
                        {
                            row.map((content, index) => {
                                return (<Cell key={index}>Content {content}</Cell>)
                            })
                        }
                    </Row>
                )
            })
        }
    </Body>
</Table>
```

Here a more complex example of the Table component with most of its features:

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;

initialState = {
    rows: [1, 2, 3, 4, 5],
    selectedRowIds: [],
};

function isSelected(rowId) {
    return state.selectedRowIds.includes(rowId);
}

const handleRowSelectionChange = (rowId, checked) => {
    if (checked) {
        setState({
            selectedRowIds: [...state.selectedRowIds, rowId],
        });
    } else {
        setState({
            selectedRowIds: state.selectedRowIds.filter((selectedRowId) => selectedRowId !== rowId),
        });
    }
};

const handleAllSelectionChange = (allSelected) => {
    if (allSelected) {
        setState({
            selectedRowIds: [...state.rows],
        });
    } else {
        setState({
            selectedRowIds: [],
        });
    }
};

const buttons = [{
    icon: 'fa-pencil',
    onClick: (rowId) => {
        alert(`You selected the row with the id ${rowId}. Imagine you could edit this row now... Mind = blown!`);
    },
}];

<Table 
    selectMode="multiple"
    buttons={buttons}
    onRowSelectionChange={handleRowSelectionChange}
    onAllSelectionChange={handleAllSelectionChange}>
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
    </Header>
    <Body>
        {
            state.rows.map((rowId, index) => {
                return (
                    <Row 
                        key={index}
                        id={rowId}
                        selected={isSelected(rowId)}>
                        <Cell>Content 1</Cell>
                        <Cell>Content 2</Cell>
                        <Cell>Content 3</Cell>
                        <Cell>Content 4</Cell>
                    </Row>
                )
            })
        }
    </Body>
</Table>
```

A Table can be used to render tree structured data by adding a depth property to the row element:

```
const Header = Table.Header;
const Body = Table.Body;
const Row = Table.Row;
const Cell = Table.Cell;
const HeaderCell = Table.HeaderCell;


const buttons = [{
        icon: 'su-pen',
        onClick: (rowId) => {
            alert(`You selected the row with the id ${rowId}. Imagine you could edit this row now... Mind = blown!`);
        },
    },
    {
        icon: 'su-add',
        onClick: (rowId) => {
            alert(`You selected the row with the id ${rowId}. Imagine you could edit this row now... Mind = blown!`);
        },
    }];

<Table
    selectMode="multiple"
    buttons={buttons}
    selectInFirstCell="true">
    <Header>
        <HeaderCell>Column 1</HeaderCell>
        <HeaderCell>Column 2</HeaderCell>
        <HeaderCell>Column 3</HeaderCell>
        <HeaderCell>Column 4</HeaderCell>
        <HeaderCell>Column 5</HeaderCell>
    </Header>
    <Body>
        <Row hasChildren="true" expanded="true">
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
        <Row depth="1">
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
        <Row depth="1" hasChildren="true" expanded="true">
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
        <Row depth="2">
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
        <Row depth="2">
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
        <Row>
            <Cell>Content 1</Cell>
            <Cell>Content 2</Cell>
            <Cell>Content 3</Cell>
            <Cell>Content 4</Cell>
            <Cell>Content 5</Cell>
        </Row>
    </Body>
</Table>
```
