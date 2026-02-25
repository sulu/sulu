// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Table from '../Table';
import Header from '../Header';
import Body from '../Body';
import Row from '../Row';
import Cell from '../Cell';
import HeaderCell from '../HeaderCell';

test('Render the Table component', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render the Table component with a skin', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render the Table component with shrunken cells', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render the Table component in tree structure', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render an empty table', () => {
    const placeholderText = 'No entries';

    const {asFragment} = render(
        <Table placeholderText={placeholderText}>
            <Header>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
                <HeaderCell>Column Title</HeaderCell>
            </Header>
            <Body />
        </Table>
    );

    expect(asFragment()).toMatchSnapshot();
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

    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render a table with different buttons for each row', () => {
    const buttons = [{
        icon: 'fa-pencil',
        onClick: jest.fn(),
    }];

    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render a table with disabled rows', () => {
    const buttons = [{
        icon: 'fa-pencil',
        onClick: jest.fn(),
    }];

    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Table buttons should implement an onClick handler', async() => {
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

    expect(clickSpy).toHaveBeenCalledTimes(0);

    const actionButtons = screen.getAllByRole('button');

    await userEvent.click(actionButtons[0]);
    await userEvent.click(actionButtons[1]);

    expect(clickSpy).toBeCalledWith(19, 0);
    expect(clickSpy).toBeCalledWith(25, 1);
    expect(clickSpy).toHaveBeenCalledTimes(2);
});

test('Table buttons should not call onClick handler if button is disabled', async() => {
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

    expect(clickSpy).toHaveBeenCalledTimes(0);

    const actionButtons = screen.getAllByRole('button');

    await userEvent.click(actionButtons[0]);
    await userEvent.click(actionButtons[1]);

    expect(clickSpy).not.toBeCalled();
});

test('Render the Table component in single selection mode', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Clicking on the radio button should call onRowSelectionChange with the row-id', async() => {
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

    await userEvent.click(screen.getByRole('radio'));

    expect(onChangeSpy).toHaveBeenCalledWith(rowId, undefined);
});

test('Render the Table component in multiple selection mode', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Render the Table component in multiple selection mode with select inside first cell', () => {
    const {asFragment} = render(
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

    expect(asFragment()).toMatchSnapshot();
});

test('Clicking a checkbox should call onRowSelectionChange with the selection state and row-id', async() => {
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

    await userEvent.click(checkboxes[1]);

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

test('Clicking the select-all checkbox should call the onAllSelectionChange callback', async() => {
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

    await userEvent.click(screen.getAllByRole('checkbox')[0]);

    expect(onChangeSpy).toHaveBeenCalledWith(true);
});

test('Header cells with a defined sortOrder must show a sort indicator', () => {
    const clickSpy = jest.fn();

    const {asFragment} = render(
        <Table>
            <Header>
                <HeaderCell onClick={clickSpy} sortOrder="asc">ColumnTitle</HeaderCell>
                <HeaderCell onClick={clickSpy} sortOrder="desc">ColumnTitle</HeaderCell>
                <HeaderCell>ColumnTitle</HeaderCell>
            </Header>
        </Table>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Header cells with an attached onClick handler should be clickable', async() => {
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

    await userEvent.click(screen.getByRole('button', {name: 'Column Title'}));

    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test(
    'Header cells with an attached name should call onClick callback with name and new sortOrder',
    async() => {
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

        const headerCells = screen.getAllByRole('columnheader');
        const columnButtons = headerCells.map((headerCell) => within(headerCell).getByRole('button'));

        await userEvent.click(columnButtons[0]);
        expect(clickSpy).lastCalledWith('column1', 'asc');

        await userEvent.click(columnButtons[1]);
        expect(clickSpy).lastCalledWith('column2', 'desc');

        await userEvent.click(columnButtons[2]);
        expect(clickSpy).lastCalledWith('column3', 'asc');
    }
);

test('Collapse should be called correctly', async() => {
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

    await userEvent.click(screen.getAllByRole('button', {name: 'su-angle-down'})[1]);

    expect(onRowCollapse).toHaveBeenCalledTimes(1);
});

test('Expand should be called correctly', async() => {
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

    await userEvent.click(screen.getByRole('button', {name: 'su-angle-right'}));

    expect(onRowExpand).toHaveBeenCalledTimes(1);
});
