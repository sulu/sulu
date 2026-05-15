// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {Button, Table} from 'sulu-admin-bundle/components';
import TargetGroupRules from '../TargetGroupRules';
import ruleRegistry from '../registries/ruleRegistry';
import RuleOverlayComponent from '../RuleOverlay';

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    const Button = jest.fn(function Button(props) {
        return (
            <button disabled={props.disabled} onClick={props.onClick} type="button">
                {props.icon || props.children}
            </button>
        );
    });

    const ButtonGroup = jest.fn(function ButtonGroup(props) {
        return <div>{props.children}</div>;
    });

    const Table: any = jest.fn(function Table(props) {
        return <table>{props.children}</table>;
    });

    Table.Header = function Header(props) {
        return (
            <thead>
                <tr>{props.children}</tr>
            </thead>
        );
    };

    Table.HeaderCell = function HeaderCell(props) {
        return <th>{props.children}</th>;
    };

    Table.Body = function Body(props) {
        return <tbody>{props.children}</tbody>;
    };

    Table.Row = function Row(props) {
        return <tr>{props.children}</tr>;
    };

    Table.Cell = function Cell(props) {
        return <td>{props.children}</td>;
    };

    return {
        ...actual,
        Button,
        ButtonGroup,
        Table,
    };
});

jest.mock('../RuleOverlay', () => jest.fn(() => null));

jest.mock('../registries/ruleRegistry', () => ({
    getAll: jest.fn(),
    get: jest.fn(),
}));

function getLatestRuleOverlayProps() {
    const calls = (RuleOverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestTableProps() {
    const calls = (Table: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestButtonProps(icon: string) {
    const calls = (Button: any).mock.calls;

    for (let i = calls.length - 1; i >= 0; i--) {
        if (calls[i][0].icon === icon) {
            return calls[i][0];
        }
    }

    throw new Error('Button with icon "' + icon + '" not found');
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

test('Add a new rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    act(() => {
        getLatestButtonProps('su-plus').onClick();
    });

    act(() => {
        getLatestRuleOverlayProps().onConfirm({
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
        getLatestTableProps().buttons[0].onClick(0, 0);
    });

    expect(getLatestRuleOverlayProps().value).toEqual({
        conditions: [],
        frequency: 1,
        title: 'Rule 1',
    });

    act(() => {
        getLatestRuleOverlayProps().onConfirm({
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

test('Close without adding a new rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(getLatestRuleOverlayProps().open).toEqual(false);

    act(() => {
        getLatestButtonProps('su-plus').onClick();
    });

    expect(getLatestRuleOverlayProps().open).toEqual(true);

    act(() => {
        getLatestRuleOverlayProps().onClose();
    });

    expect(getLatestRuleOverlayProps().open).toEqual(false);
    expect(changeSpy).not.toBeCalled();
});

test('Remove rules', () => {
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
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 3',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(getLatestButtonProps('su-trash-alt').disabled).toEqual(true);

    act(() => {
        getLatestTableProps().onRowSelectionChange(1, true);
        getLatestTableProps().onRowSelectionChange(2, true);
    });

    expect(getLatestButtonProps('su-trash-alt').disabled).toEqual(false);

    act(() => {
        getLatestButtonProps('su-trash-alt').onClick();
    });

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ]);
});
