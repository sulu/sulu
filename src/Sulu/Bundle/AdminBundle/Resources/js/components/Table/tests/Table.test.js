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

test('Render an empty table', () => {
    const placeholderText = 'No entries';

    expect(render(
        <Table placeholderText={placeholderText}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body></Body>
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
    const onClickSpy = jest.fn();
    const buttons = [{
        icon: 'fa-pencil',
        onClick: onClickSpy,
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

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    table.find('.buttonCell button').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
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

test('Header cells with an attacked onClick handler should be clickable', () => {
    const onClickSpy = jest.fn();

    const table = mount(
        <Table>
            <Header>
                <HeaderCell onClick={onClickSpy}>
                    Column Title
                </HeaderCell>
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
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
