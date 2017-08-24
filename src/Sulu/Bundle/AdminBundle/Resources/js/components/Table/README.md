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


```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

initialState = { 
    selectedRows: [],
};

const isSelected = (id) => {
    return state.selectedRows.indexOf(id) > -1;
};

const tableProps = {
    selectMode: 'single',
    controls: [
        {
            icon: 'pencil',
            onClick: (rowId) => {}
        },
    ],
    onRowSelectionChange: (rowId, selected) => {
        setState({
            selectedRows: [rowId]
        });
    },
    onAllSelectionChange: (allSelected) => {
        if (allSelected) {
            setState({
                selectedRows: tableData.body.map((row) => row.id),
            });
        } else {
            setState({
                selectedRows: [],
            });
        }
    },
};

const tableData = {
    header: [
        { 
            data: 'Type',
            sortMode: 'ascending',
            onClick: () => {

            },
        },
        { 
            data: 'Name',
        },
        { 
            data: 'Author',
        },
        { 
            data: 'Date',
            sortMode: 'descending',
            onClick: () => {
                console.log('hello');
            },
        },
        { 
            data: 'Subversion',
        },
        { 
            data: 'Uploadgröße',
            onClick: () => {

            },
        },
    ],
    body: [
        {
            id: 1,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 2,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 3,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 4,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 5,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 6,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
        {
            id: 7,
            data: ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB'],
        },
    ],
};

<Table {...tableProps}>
    <Header>
        <Row>
            {
                tableData.header.map((headerCell, index) => {
                    const handleOnClick = headerCell.onClick;
                    return (
                        <HeaderCell 
                            key={index}
                            sortMode={headerCell.sortMode}
                            onClick={handleOnClick}>
                            {headerCell.data}
                        </HeaderCell>
                    );
                })
            }
        </Row>
    </Header>
    <Body>
        {
            tableData.body.map((row, index) => {
                return (
                    <Row 
                        key={index}
                        id={row.id}
                        selected={isSelected(row.id)}>
                        {
                            row.data.map((cell, index) => {
                                return (
                                    <Cell key={index}>
                                        {cell}
                                    </Cell>
                                );
                            })
                        }
                    </Row>
                )
            })
        }
    </Body>
</Table>
```
