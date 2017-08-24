/* eslint-disable flowtype/require-valid-file-annotation */
import {render, mount} from 'enzyme';
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

test('Render an empty table', () => {
    expect(render(
        <Table>
            <Header>
                <Row>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                </Row>
            </Header>
            <Body></Body>
        </Table>
    )).toMatchSnapshot();
});

test('Render a table with control buttons', () => {
    const controls = [{
        icon: 'pencil',
        onClick: jest.fn(),
    }];

    expect(render(
        <Table controls={controls}>
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
            </Body>
        </Table>
    )).toMatchSnapshot();
});

test('Table controls should implement an onClick handler', () => {
    const onClickSpy = jest.fn();
    const controls = [{
        icon: 'pencil',
        onClick: onClickSpy,
    }];

    const table = mount(
        <Table controls={controls}>
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
            </Body>
        </Table>
    );

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    table.find('.controlCell > button').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});

test('Render the Table component in single selection mode', () => {
    expect(render(
        <Table selectMode="single">
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
            </Body>
        </Table>
    )).toMatchSnapshot();
});

test('Table should implement onRowSelectionChange method which has the id of the selected row', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'single',
        onRowSelectionChange: onChangeSpy,
    };
    const rowId = 'test-row-id';
    const table = mount(
        <Table {...props}>
            <Header>
                <Row>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                    <HeaderCell>Column Title</HeaderCell>
                </Row>
            </Header>
            <Body>
                <Row id={rowId}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    expect(onChangeSpy).toHaveBeenCalledTimes(0);
    table.find('Row Radio input').simulate('change');
    expect(onChangeSpy).toHaveBeenCalledWith(rowId, undefined);
});
