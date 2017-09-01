// @flow
import {observable} from 'mobx';
import React from 'react';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import {Table, Body, Header, Cell, HeaderCell, Row} from '../../components/Table';

class List extends React.PureComponent<*> {
    @observable tableData = {
        header: [
            'Type of',
            'Name',
            'Author',
            'Date',
            'Subversion',
            'Uploadgröße',
        ],
        body: [
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
            [
                'Blog',
                'My first 100 day in Vorarlberg',
                'Adrian Sieber',
                '24.12.2017',
                'Github',
                '20 MB',
            ],
        ],
    };

    render() {
        return (
            <div>
                <h1>List</h1>
                <a href="#/snippets/123">To the Form</a>
                <Table>
                    <Header>
                        {
                            this.tableData.header.map((headerCell, index) => {
                                return (
                                    <HeaderCell key={index}>
                                        {headerCell}
                                    </HeaderCell>
                                );
                            })
                        }
                    </Header>
                    <Body>
                        {
                            this.tableData.body.map((row, index) => {
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
                                );
                            })
                        }
                    </Body>
                </Table>
            </div>
        );
    }
}

export default withToolbar(List, function() {
    return {
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
