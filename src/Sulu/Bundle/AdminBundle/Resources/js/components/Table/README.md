The Table component consists out of six parts: Table, Header, Body, Row, Cell and HeaderCell. All of them has to be
imported in order to build a table.

Here is an example of a simple table. The structure of the Table component is identical to a normal HTML table.

```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

<Table>
    <Header>
        <Row>
            <HeaderCell>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
            <HeaderCell>Column 4</HeaderCell>
            <HeaderCell>Column 5</HeaderCell>
            <HeaderCell>Column 6</HeaderCell>
            <HeaderCell>Column 7</HeaderCell>
            <HeaderCell>Column 8</HeaderCell>
            <HeaderCell>Column 9</HeaderCell>
        </Row>
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

An empty table components will be rendered as follows. You can set a `placeholderText`.

```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

<Table placeholderText="Awwww, this little fella has no entries...">
    <Header>
        <Row>
            <HeaderCell>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
            <HeaderCell>Column 4</HeaderCell>
            <HeaderCell>Column 5</HeaderCell>
            <HeaderCell>Column 6</HeaderCell>
            <HeaderCell>Column 7</HeaderCell>
            <HeaderCell>Column 8</HeaderCell>
            <HeaderCell>Column 9</HeaderCell>
        </Row>
    </Header>
    <Body></Body>
</Table>
```

You can add control elements to the table which will always be prepended as the first cell in every row.
The control elements are basically buttons on which you can set the `onclick` handler and the icon name by passing an 
array to the `controls` property. Hover the first cell and click on the button to change the boring text inside each 
row.

```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

initialState = {
    rows: [
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
        ['Boring Text', 'Boring Text', 'Boring Text', 'Boring Text', 'Boring Text'],
    ],
};

const controls = [{
    icon: 'heart',
    onClick: (rowId) => {
        state.rows[rowId] = state.rows[rowId].map((cell) => 'You are awesome 😘');
        const newRows = state.rows;

        setState({
            rows: newRows,
        })
    },
}];

<Table controls={controls}>
    <Header>
        <Row>
            <HeaderCell>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
            <HeaderCell>Column 4</HeaderCell>
            <HeaderCell>Column 5</HeaderCell>
        </Row>
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
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

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
        <Row>
            <HeaderCell>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
            <HeaderCell>Column 4</HeaderCell>
            <HeaderCell>Column 5</HeaderCell>
            <HeaderCell>Column 6</HeaderCell>
            <HeaderCell>Column 7</HeaderCell>
            <HeaderCell>Column 8</HeaderCell>
            <HeaderCell>Column 9</HeaderCell>
        </Row>
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

The multiple selection mode prepends the `Checkbox` component to each row and also a "select-all" checkbox to the
header.

```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

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
        <Row>
            <HeaderCell>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
            <HeaderCell>Column 4</HeaderCell>
            <HeaderCell>Column 5</HeaderCell>
            <HeaderCell>Column 6</HeaderCell>
            <HeaderCell>Column 7</HeaderCell>
            <HeaderCell>Column 8</HeaderCell>
            <HeaderCell>Column 9</HeaderCell>
        </Row>
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
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

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
        <Row>
            <HeaderCell onClick={handleSortColumnA} sortMode={state.sortMode}>Column 1</HeaderCell>
            <HeaderCell>Column 2</HeaderCell>
            <HeaderCell>Column 3</HeaderCell>
        </Row>
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
