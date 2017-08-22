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
