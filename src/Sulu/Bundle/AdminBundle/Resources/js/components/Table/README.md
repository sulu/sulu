```
const Table = require('./Table').default;
const Header = require('./Header').default;
const Body = require('./Body').default;
const Row = require('./Row').default;
const Cell = require('./Cell').default;
const HeaderCell = require('./HeaderCell').default;

const controlItems = [
    {
        icon: 'pencil',
        onClick: (rowId) => {}
    },
    {
        icon: 'pencil',
        onClick: (rowId) => {}
    }
];

handleRowClick = (rowId) => {

};

handleRowSelection = (rowIds) => {

};

<Table 
    controls={controlItems}
    onRowSelection={handleRowSelection}>
    <Header>
        <Row>
            <HeaderCell>
                Type
            </HeaderCell>
            <HeaderCell>
                Name
            </HeaderCell>
            <HeaderCell>
                Author
            </HeaderCell>
            <HeaderCell>
                Date
            </HeaderCell>
            <HeaderCell>
                Subversion
            </HeaderCell>
            <HeaderCell>
                Uploadgröße
            </HeaderCell>
            <HeaderCell>
                Dateigröße
            </HeaderCell>
            <HeaderCell>
                Uploadgröße
            </HeaderCell>
            <HeaderCell>
                Dateigröße
            </HeaderCell>
            <HeaderCell>
                Uploadgröße
            </HeaderCell>
            <HeaderCell>
                Dateigröße
            </HeaderCell>
        </Row>
    </Header>
    <Body>
        <Row selectable={true}>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>   
        </Row>
        <Row>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>        
        </Row>
        <Row>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>        
        </Row>
        <Row>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>        
        </Row>
        <Row>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>        
        </Row>
        <Row>
            <Cell>
                Blog
            </Cell>
            <Cell>
                Meine ersten 100 Tage MASSIVE ART
            </Cell>
            <Cell>
                Adrian Sieber
            </Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>
            <Cell></Cell>   
            <Cell></Cell>
            <Cell></Cell>        
        </Row>
    </Body>    
</Table>
```
