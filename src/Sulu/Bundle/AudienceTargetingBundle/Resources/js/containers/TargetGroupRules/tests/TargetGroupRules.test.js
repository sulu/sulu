// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TargetGroupRules from '../TargetGroupRules';
import ruleRegistry from '../registries/ruleRegistry';
import ruleTypeRegistry from '../registries/ruleTypeRegistry';
import KeyValue from '../ruleTypes/KeyValue';
import SingleSelect from '../ruleTypes/SingleSelect';

let mockOverlayProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Overlay: jest.fn((props) => {
            mockOverlayProps = props;

            if (!props.open) {
                return null;
            }

            return mockReact.createElement(
                'div',
                {
                    'data-testid': 'overlay',
                },
                mockReact.createElement('h2', {}, props.title),
                props.children,
                mockReact.createElement(
                    'button',
                    {onClick: () => props.onConfirm(), type: 'button'},
                    props.confirmText
                ),
                mockReact.createElement('button', {
                    'aria-label': 'su-times',
                    onClick: () => props.onClose(),
                    type: 'button',
                })
            );
        }),
    };
});

jest.mock('sulu-admin-bundle/utils/Translator');

const rules = {
    browser: {
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
    },
    query_string: {
        name: 'Query String',
        type: {
            name: 'key_value',
            options: {
                keyName: 'parameter',
                keyPlaceholder: 'Parameter',
                valueName: 'value',
                valuePlaceholder: 'Value',
            },
        },
    },
};

beforeEach(() => {
    mockOverlayProps = {};
    ruleRegistry.setRules(rules);
    ruleTypeRegistry.add('key_value', KeyValue);
    ruleTypeRegistry.add('select', SingleSelect);
});

afterEach(() => {
    ruleRegistry.clear();
    ruleTypeRegistry.clear();
});

test('Render an empty list of rules', () => {
    const {asFragment} = render(<TargetGroupRules onChange={jest.fn()} value={[]} />);

    expect(asFragment()).toMatchSnapshot();
    expect(mockOverlayProps).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Render a list of rules', () => {
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
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'su-plus'}));

    expect(screen.getByText('sulu_audience_targeting.configure_rule')).toBeInTheDocument();

    await user.type(screen.getByRole('textbox'), 'Rule 2');
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'sulu_audience_targeting.each_session'}));

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toHaveBeenCalledWith([
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

test('Edit an existing rule', async() => {
    const user = userEvent.setup();
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

    await user.click(screen.getAllByRole('button', {name: 'su-pen'})[0]);

    expect(screen.getByDisplayValue('Rule 1')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_audience_targeting.each_page_visit/})).toBeInTheDocument();

    await user.clear(screen.getByDisplayValue('Rule 1'));
    await user.type(screen.getByRole('textbox'), 'Rule 1 edited');
    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.each_page_visit/}));
    await user.click(screen.getByRole('button', {name: 'sulu_audience_targeting.first_visit'}));

    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.add_condition/}));
    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.add_condition/}));

    await user.click(screen.getAllByRole('button', {name: /sulu_admin.please_choose/})[0]);
    await user.click(screen.getByRole('button', {name: 'Browser'}));
    await user.click(screen.getAllByRole('button', {name: /sulu_admin.please_choose/})[0]);
    await user.click(screen.getByRole('button', {name: 'Firefox'}));

    await user.click(screen.getByRole('button', {name: /sulu_admin.please_choose/}));
    await user.click(screen.getByRole('button', {name: 'Query String'}));
    await user.type(screen.getByPlaceholderText('Parameter'), 'parameter');
    await user.type(screen.getByPlaceholderText('Value'), 'value');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toHaveBeenCalledWith([
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
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(screen.queryByText('sulu_audience_targeting.configure_rule')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-plus'}));

    expect(screen.getByText('sulu_audience_targeting.configure_rule')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(screen.queryByText('sulu_audience_targeting.configure_rule')).not.toBeInTheDocument();
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Remove rules', async() => {
    const user = userEvent.setup();
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

    expect(screen.getByRole('button', {name: 'su-trash-alt'})).toBeDisabled();

    await user.click(screen.getAllByRole('checkbox')[2]);
    await user.click(screen.getAllByRole('checkbox')[3]);

    expect(screen.getByRole('button', {name: 'su-trash-alt'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(changeSpy).toHaveBeenCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ]);
});
