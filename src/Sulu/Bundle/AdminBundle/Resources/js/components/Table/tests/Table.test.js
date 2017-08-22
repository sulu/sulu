/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import Table from '../Table';
import Header from '../Header';
import Body from '../Body';
import Row from '../Row';
import Cell from '../Cell';
import HeaderCell from '../HeaderCell';

afterEach(() => document.body.innerHTML = '');

test('Render the Table component', () => {
    expect(render(
        <Table>
            <Header>
                <Row>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                </Row>
            </Header>
            <Body>
                <Row>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    )).toMatchSnapshot();
});
