```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

const tableData = {
    header: ['Type', 'Name', 'Author', 'Date', 'Subversion', 'Uploadgröße', 'Dateigröße'],
    body: [
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
        ['Blog', 'Meine ersten 100 Tage MASSIVE ART', 'Adrian Sieber', '24.12.2017', 'Github', '20 MB', 'Test'],
    ],
};

<Table>
    <Header>
        <Row>
            {
                tableData.header.map((headerCell, index) => {
                    return (
                        <HeaderCell key={index}>
                            {headerCell}
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
                    <Row key={index}>
                        {
                            row.map((cell, index) => {
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
