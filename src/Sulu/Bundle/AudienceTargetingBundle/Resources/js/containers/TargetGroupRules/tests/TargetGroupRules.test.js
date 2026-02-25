// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Table} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import TargetGroupRules from '../TargetGroupRules';
import ruleRegistry from '../registries/ruleRegistry';
import RuleOverlay from '../RuleOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Button = jest.fn(function ButtonMock({disabled, icon, onClick}) {
        return React.createElement(
            'button',
            {disabled, onClick, type: 'button'},
            icon
        );
    });

    const ButtonGroupMock = function ButtonGroupMock({children}) {
        return React.createElement('div', {'data-testid': 'button-group'}, children);
    };

    const TableBase = jest.fn(function TableMock({children}) {
        return React.createElement('div', {'data-testid': 'table'}, children);
    });
    const Table: any = TableBase;

    Table.Header = function TableHeaderMock({children}) {
        return React.createElement('div', {'data-testid': 'table-header'}, children);
    };
    Table.HeaderCell = function TableHeaderCellMock({children}) {
        return React.createElement('div', {'data-testid': 'table-header-cell'}, children);
    };
    Table.Body = function TableBodyMock({children}) {
        return React.createElement('div', {'data-testid': 'table-body'}, children);
    };
    Table.Row = function TableRowMock({children, selected}) {
        return React.createElement('div', {'data-selected': selected}, children);
    };
    Table.Cell = function TableCellMock({children}) {
        return React.createElement('div', {'data-testid': 'table-cell'}, children);
    };

    return {
        Button,
        ButtonGroup: ButtonGroupMock,
        Table,
    };
});

jest.mock('../RuleOverlay', () => jest.fn(function RuleOverlayMock({open}) {
    return <div data-testid={open ? 'rule-overlay-open' : 'rule-overlay-closed'} />;
}));

jest.mock('../registries/ruleTypeRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../registries/ruleRegistry', () => ({
    getAll: jest.fn(),
    get: jest.fn(),
}));

function getLatestTableProps() {
    const tableMock: any = Table;
    return getLatestMockProps(tableMock);
}

function getLatestOverlayProps() {
    return getLatestMockProps((RuleOverlay: any));
}

function getButtonByIcon(icon: string) {
    return screen.getByRole('button', {name: icon});
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render an empty list of rules', () => {
    const {asFragment} = render(<TargetGroupRules onChange={jest.fn()} value={[]} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render a list of rules', () => {
    ruleRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'browser':
                return {
                    name: 'Browser',
                    type: {
                        name: 'select',
                        options: {
                            name: 'browser',
                            options: [
                                {id: 'firefox', name: 'Firefox'},
                                {id: 'chrome', name: 'Chrome'},
                            ],
                        },
                    },
                };
            case 'query_string':
                return {
                    name: 'Query String',
                    type: {
                        name: 'key_value',
                        options: {
                            keyName: 'parameter',
                            valueName: 'value',
                        },
                    },
                };
        }
    });

    const value = [
        {
            conditions: [
                {
                    condition: {browser: 'Opera'},
                    type: 'browser',
                },
            ],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [
                {
                    condition: {browser: 'Opera'},
                    type: 'browser',
                },
                {
                    condition: {parameter: 'test', value: 'value'},
                    type: 'query_string',
                },
            ],
            frequency: 2,
            title: 'Rule 2',
        },
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 3',
        },
    ];

    const {asFragment} = render(<TargetGroupRules onChange={jest.fn()} value={value} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Add a new rule', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    await user.click(getButtonByIcon('su-plus'));

    expect(getLatestOverlayProps().open).toEqual(true);

    act(() => {
        getLatestOverlayProps().onConfirm({
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        });
    });

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ]);
});

test('Edit an existing rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    act(() => {
        getLatestTableProps().buttons[0].onClick('0', 0);
    });

    expect(getLatestOverlayProps().value).toEqual(value[0]);

    act(() => {
        getLatestOverlayProps().onConfirm({
            conditions: [
                {
                    condition: {
                        browser: 'firefox',
                    },
                    type: 'browser',
                },
                {
                    condition: {
                        parameter: 'parameter',
                        value: 'value',
                    },
                    type: 'query_string',
                },
            ],
            frequency: 3,
            title: 'Rule 1 edited',
        });
    });

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [
                {
                    condition: {
                        browser: 'firefox',
                    },
                    type: 'browser',
                },
                {
                    condition: {
                        parameter: 'parameter',
                        value: 'value',
                    },
                    type: 'query_string',
                },
            ],
            frequency: 3,
            title: 'Rule 1 edited',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ]);
});

test('Close without adding a new rule', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(getLatestOverlayProps().open).toEqual(false);

    await user.click(getButtonByIcon('su-plus'));
    expect(getLatestOverlayProps().open).toEqual(true);

    act(() => {
        getLatestOverlayProps().onClose();
    });

    expect(getLatestOverlayProps().open).toEqual(false);
    expect(changeSpy).not.toBeCalled();
});

test('Remove rules', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 3',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(getButtonByIcon('su-trash-alt')).toBeDisabled();

    act(() => {
        getLatestTableProps().onRowSelectionChange(1, true);
        getLatestTableProps().onRowSelectionChange(2, true);
    });

    expect(getButtonByIcon('su-trash-alt')).toBeEnabled();

    await user.click(getButtonByIcon('su-trash-alt'));

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ]);
});
