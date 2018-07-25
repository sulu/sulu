// @flow
import {render, mount} from 'enzyme';
import React from 'react';
import Table from '../Table';
import Header from '../Header';
import Body from '../Body';
import Row from '../Row';
import Cell from '../Cell';
import HeaderCell from '../HeaderCell';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Render the Table component', () => {
    expect(render(
        <Table>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

test('Render the Table component in tree structure', () => {
    expect(render(
        <Table>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row depth={0} hasChildren={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={1} hasChildren={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={2}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    )).toMatchSnapshot();
});

test('Render an empty table', () => {
    const placeholderText = 'No entries';

    expect(render(
        <Table placeholderText={placeholderText}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body />
        </Table>
    )).toMatchSnapshot();
});

test('Render a table with buttons', () => {
    const buttons = [{
        icon: 'fa-pencil',
        onClick: jest.fn(),
    }];

    expect(render(
        <Table buttons={buttons}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

test('Table buttons should implement an onClick handler', () => {
    const clickSpy = jest.fn();
    const buttons = [{
        icon: 'fa-pencil',
        onClick: clickSpy,
    }];

    const table = mount(
        <Table buttons={buttons}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

    expect(clickSpy).toHaveBeenCalledTimes(0);
    table.find('.buttonCell button').simulate('click');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('Render the Table component in single selection mode', () => {
    expect(render(
        <Table selectMode="single">
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

test('Clicking on the radio button should call onRowSelectionChange with the row-id', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'single',
        onRowSelectionChange: onChangeSpy,
    };
    const rowId = 'test-row-id';
    const table = mount(
        <Table {...props}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

test('Render the Table component in multiple selection mode', () => {
    expect(render(
        <Table selectMode="multiple">
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

test('Clicking a checkbox should call onRowSelectionChange with the selection state and row-id', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'multiple',
        onRowSelectionChange: onChangeSpy,
    };
    const rowIdOne = 'test-row-id-1';
    const rowIdTwo = 'test-row-id-2';
    const table = mount(
        <Table {...props}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row id={rowIdOne}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row id={rowIdTwo}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    expect(onChangeSpy).toHaveBeenCalledTimes(0);

    const checkboxOne = table.find('Row').at(0).find('Checkbox input');
    checkboxOne.at(0).instance().checked = true;

    checkboxOne.simulate('change');
    expect(onChangeSpy).toHaveBeenCalledWith(rowIdOne, true);
});

test('Clicking the select-all checkbox should call the onAllSelectionChange callback', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'multiple',
        onAllSelectionChange: onChangeSpy,
    };
    const table = mount(
        <Table {...props}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

    const allCheckbox = table.find('Header').find('Checkbox input');
    allCheckbox.at(0).instance().checked = true;

    allCheckbox.simulate('change');
    expect(onChangeSpy).toHaveBeenCalledWith(true);
});

test('Header cells with a defined sortOrder must show a sort indicator', () => {
    const clickSpy = jest.fn();

    expect(render(
        <Table>
            <Header>
                <HeaderCell onClick={clickSpy} sortOrder="asc">ColumnTitle</HeaderCell>
                <HeaderCell onClick={clickSpy} sortOrder="desc">ColumnTitle</HeaderCell>
                <HeaderCell>ColumnTitle</HeaderCell>
            </Header>
        </Table>
    )).toMatchSnapshot();
});

test('Header cells with an attached onClick handler should be clickable', () => {
    const clickSpy = jest.fn();

    const table = mount(
        <Table>
            <Header>
                <HeaderCell onClick={clickSpy} name="column1">Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
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

    table.find('HeaderCell').at(0).find('button').simulate('click');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('Header cells with an attached name should call the onClick callback with the name and the new sortOrder', () => {
    const clickSpy = jest.fn();

    const table = mount(
        <Table>
            <Header>
                <HeaderCell onClick={clickSpy} name="column1">Column Title</HeaderCell>
                <HeaderCell onClick={clickSpy} name="column2" sortOrder="asc">Column Title</HeaderCell>
                <HeaderCell onClick={clickSpy} name="column3" sortOrder="desc">Column Title</HeaderCell>
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

    table.find('HeaderCell').at(0).find('button').simulate('click');
    expect(clickSpy).lastCalledWith('column1', 'asc');

    table.find('HeaderCell').at(1).find('button').simulate('click');
    expect(clickSpy).lastCalledWith('column2', 'desc');

    table.find('HeaderCell').at(2).find('button').simulate('click');
    expect(clickSpy).lastCalledWith('column3', 'asc');
});

test('Collapse should be called correctly', () => {
    const onRowCollapse = jest.fn();

    const table = mount(
        <Table
            onRowCollapse={onRowCollapse}
        >
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row depth={0} hasChildren={true} expanded={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={1} hasChildren={true} expanded={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={2}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    table.find('Row').at(1).find('span.toggleIcon Icon').simulate('click');
    expect(onRowCollapse).toHaveBeenCalledTimes(1);
});

test('Expand should be called correctly', () => {
    const onRowExpand = jest.fn();

    const table = mount(
        <Table
            onRowExpand={onRowExpand}
        >
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row depth={0} hasChildren={true} expanded={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={1} hasChildren={true} expanded={false}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={2}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    table.find('Row').at(1).find('span.toggleIcon Icon').simulate('click');
    expect(onRowExpand).toHaveBeenCalledTimes(1);
});
