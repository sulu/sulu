/* eslint-disable testing-library/prefer-user-event */
// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Table from '../Table';
import Header from '../Header';
import Body from '../Body';
import Row from '../Row';
import Cell from '../Cell';
import HeaderCell from '../HeaderCell';

function expectSnapshot(element: any) {
    const {asFragment} = render(element);
    expect(asFragment()).toMatchSnapshot();
}

test('Render the Table component', () => {
    expectSnapshot(
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
    );
});

test('Render the Table component with a skin', () => {
    expectSnapshot(
        <Table skin="light">
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
    );
});

test('Render the Table component with shrunken cells', () => {
    expectSnapshot(
        <Table>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row>
                    <Cell width="shrink">Column Text</Cell>
                    <Cell width="shrink">Column Text</Cell>
                    <Cell width="shrink">Column Text</Cell>
                </Row>
                <Row>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );
});

test('Render the Table component in tree structure', () => {
    expectSnapshot(
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
    );
});

test('Render an empty table', () => {
    const placeholderText = 'No entries';

    expectSnapshot(
        <Table placeholderText={placeholderText}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body />
        </Table>
    );
});

test('Render a table with buttons', () => {
    const buttons = [
        {
            icon: 'fa-pencil',
            onClick: jest.fn(),
        },
        {
            disabled: true,
            icon: 'fa-lock',
            onClick: jest.fn(),
        },
    ];

    expectSnapshot(
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
});

test('Render a table with different buttons for each row', () => {
    const buttons = [{
        icon: 'fa-pencil',
        onClick: jest.fn(),
    }];

    expectSnapshot(
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
                <Row buttons={[{icon: 'fa-plus', onClick: jest.fn()}]}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );
});

test('Render a table with disabled rows', () => {
    const buttons = [{
        icon: 'fa-pencil',
        onClick: jest.fn(),
    }];

    expectSnapshot(
        <Table buttons={buttons}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row>
                    <Cell>Boring Row</Cell>
                    <Cell>Column 2</Cell>
                </Row>
                <Row disabled={true}>
                    <Cell>Disabled Row</Cell>
                    <Cell>Column 2</Cell>
                </Row>
            </Body>
        </Table>
    );
});

test('Table buttons should implement an onClick handler', () => {
    const clickSpy = jest.fn();
    const buttons = [{
        icon: 'fa-pencil',
        onClick: clickSpy,
    }];

    render(
        <Table buttons={buttons}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row id={19}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row id={25}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    const rowButtons = screen.getAllByRole('button', {name: 'fa-pencil'});
    expect(clickSpy).toHaveBeenCalledTimes(0);
    fireEvent.click(rowButtons[0]);
    fireEvent.click(rowButtons[1]);
    expect(clickSpy).toHaveBeenCalledWith(19, 0);
    expect(clickSpy).toHaveBeenCalledWith(25, 1);
    expect(clickSpy).toHaveBeenCalledTimes(2);
});

test('Table buttons should not call onClick handler if button is disabled', () => {
    const clickSpy = jest.fn();
    const buttons = [{
        disabled: true,
        icon: 'fa-pencil',
        onClick: clickSpy,
    }];

    render(
        <Table buttons={buttons}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row id={19}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row id={25}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    const rowButtons = screen.getAllByRole('button', {name: 'fa-pencil'});
    expect(clickSpy).toHaveBeenCalledTimes(0);
    fireEvent.click(rowButtons[0]);
    fireEvent.click(rowButtons[1]);
    expect(clickSpy).not.toHaveBeenCalled();
});

test('Render the Table component in single selection mode', () => {
    expectSnapshot(
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
    );
});

test('Clicking on the radio button should call onRowSelectionChange with the row-id', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'single',
        onRowSelectionChange: onChangeSpy,
    };
    const rowId = 'test-row-id';
    render(
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
    fireEvent.click(screen.getByRole('radio'));
    expect(onChangeSpy).toHaveBeenCalledWith(rowId, undefined);
});

test('Render the Table component in multiple selection mode', () => {
    expectSnapshot(
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
    );
});

test('Render the Table component in multiple selection mode with select inside first cell', () => {
    expectSnapshot(
        <Table onAllSelectionChange={jest.fn()} selectInFirstCell={true} selectMode="multiple">
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
});

test('Clicking a checkbox should call onRowSelectionChange with the selection state and row-id', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'multiple',
        onRowSelectionChange: onChangeSpy,
    };
    const rowIdOne = 'test-row-id-1';
    const rowIdTwo = 'test-row-id-2';
    render(
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

    const checkboxes = screen.getAllByRole('checkbox');
    fireEvent.click(checkboxes[1]);
    expect(onChangeSpy).toHaveBeenCalledWith(rowIdOne, true);
});

test('Select-all checkbox should be checked if every line is selected', () => {
    render(
        <Table selectMode="multiple">
            <Header>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row selected={true}>
                    <Cell>Column Text</Cell>
                </Row>
                <Row selected={true}>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    expect(screen.getAllByRole('checkbox')[0]).toBeChecked();
});

test('Select-all checkbox should not be checked if at least one non-disabled line is not selected', () => {
    render(
        <Table selectMode="multiple">
            <Header>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row selected={true}>
                    <Cell>Column Text</Cell>
                </Row>
                <Row selected={false}>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    expect(screen.getAllByRole('checkbox')[0]).not.toBeChecked();
});

test('Select-all checkbox should be checked if every non-disabled line is selected', () => {
    render(
        <Table selectMode="multiple">
            <Header>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row selected={true}>
                    <Cell>Column Text</Cell>
                </Row>
                <Row disabled={true}>
                    <Cell>Column Text</Cell>
                </Row>
            </Body>
        </Table>
    );

    expect(screen.getAllByRole('checkbox')[0]).toBeChecked();
});

test('Clicking the select-all checkbox should call the onAllSelectionChange callback', () => {
    const onChangeSpy = jest.fn();
    const props = {
        selectMode: 'multiple',
        onAllSelectionChange: onChangeSpy,
    };
    render(
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

    const allCheckboxes = screen.getAllByRole('checkbox');
    fireEvent.click(allCheckboxes[0]);
    expect(onChangeSpy).toHaveBeenCalledWith(true);
});

test('Header cells with a defined sortOrder must show a sort indicator', () => {
    const clickSpy = jest.fn();

    expectSnapshot(
        <Table>
            <Header>
                <HeaderCell onClick={clickSpy} sortOrder="asc">ColumnTitle</HeaderCell>
                <HeaderCell onClick={clickSpy} sortOrder="desc">ColumnTitle</HeaderCell>
                <HeaderCell>ColumnTitle</HeaderCell>
            </Header>
        </Table>
    );
});

test('Header cells with an attached onClick handler should be clickable', () => {
    const clickSpy = jest.fn();

    render(
        <Table>
            <Header>
                <HeaderCell name="column1" onClick={clickSpy}>Column Title</HeaderCell>
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

    fireEvent.click(screen.getByRole('button', {name: 'Column Title'}));
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('Header cells with an attached name should call the onClick callback with the name and the new sortOrder', () => {
    const clickSpy = jest.fn();

    render(
        <Table>
            <Header>
                <HeaderCell name="column1" onClick={clickSpy}>Column Title</HeaderCell>
                <HeaderCell name="column2" onClick={clickSpy} sortOrder="asc">Column Title</HeaderCell>
                <HeaderCell name="column3" onClick={clickSpy} sortOrder="desc">Column Title</HeaderCell>
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

    const headerButtons = screen.getAllByRole('button');
    fireEvent.click(headerButtons[0]);
    expect(clickSpy).toHaveBeenLastCalledWith('column1', 'asc');

    fireEvent.click(headerButtons[1]);
    expect(clickSpy).toHaveBeenLastCalledWith('column2', 'desc');

    fireEvent.click(headerButtons[2]);
    expect(clickSpy).toHaveBeenLastCalledWith('column3', 'asc');
});

test('Collapse should be called correctly', () => {
    const onRowCollapse = jest.fn();

    render(
        <Table onRowCollapse={onRowCollapse}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row depth={0} expanded={true} hasChildren={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={1} expanded={true} hasChildren={true}>
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

    fireEvent.click(screen.getAllByRole('button', {name: 'su-angle-down'})[1]);
    expect(onRowCollapse).toHaveBeenCalledTimes(1);
});

test('Expand should be called correctly', () => {
    const onRowExpand = jest.fn();

    render(
        <Table onRowExpand={onRowExpand}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body>
                <Row depth={0} expanded={true} hasChildren={true}>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                    <Cell>Column Text</Cell>
                </Row>
                <Row depth={1} expanded={false} hasChildren={true}>
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

    fireEvent.click(screen.getByRole('button', {name: 'su-angle-right'}));
    expect(onRowExpand).toHaveBeenCalledTimes(1);
});
