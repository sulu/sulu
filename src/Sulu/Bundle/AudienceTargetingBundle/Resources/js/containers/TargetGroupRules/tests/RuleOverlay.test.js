// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import RuleOverlay from '../RuleOverlay';
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

test('Render RuleOverlay without value', () => {
    const {asFragment} = render(
        <RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />
    );

    expect(asFragment()).toMatchSnapshot();
    expect(mockOverlayProps).toEqual(expect.objectContaining({
        confirmText: 'sulu_admin.ok',
        open: true,
        title: 'sulu_audience_targeting.configure_rule',
    }));
});

test('Write passed values to input, single select and condition list when overlay is opened', async() => {
    const user = userEvent.setup();
    const conditions = [
        {
            condition: {
                parameter: 'asdf',
                value: 'jklö',
            },
            type: 'query_string',
        },
    ];

    const {rerender} = render(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    expect(screen.getByDisplayValue('Rule 1')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_audience_targeting.each_session/})).toBeInTheDocument();
    expect(screen.getByDisplayValue('asdf')).toBeInTheDocument();
    expect(screen.getByDisplayValue('jklö')).toBeInTheDocument();

    const titleInput = screen.getByDisplayValue('Rule 1');
    await user.clear(titleInput);
    await user.type(titleInput, 'Rule 1 edited');
    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.each_session/}));
    await user.click(screen.getByRole('button', {name: 'sulu_audience_targeting.first_visit'}));
    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );
    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    expect(screen.getByDisplayValue('Rule 1')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_audience_targeting.each_session/})).toBeInTheDocument();
    expect(screen.getByDisplayValue('asdf')).toBeInTheDocument();
    expect(screen.getByDisplayValue('jklö')).toBeInTheDocument();

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );
    rerender(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />);

    expect(screen.getByRole('textbox')).toHaveValue('');
    expect(screen.getByRole('button', {name: /sulu_admin.please_choose/})).toBeInTheDocument();
    expect(screen.queryByDisplayValue('asdf')).not.toBeInTheDocument();
    expect(screen.queryByDisplayValue('jklö')).not.toBeInTheDocument();
});

test('Call confirm with the current values', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    render(<RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />);

    await user.type(screen.getByRole('textbox'), 'Rule 11');
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'sulu_audience_targeting.each_session'}));
    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.add_condition/}));
    await user.click(screen.getByRole('button', {name: /sulu_admin.please_choose/}));
    await user.click(screen.getByRole('button', {name: 'Query String'}));
    await user.type(screen.getByPlaceholderText('Parameter'), 'asdf');
    await user.type(screen.getByPlaceholderText('Value'), 'jklö');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(confirmSpy).toHaveBeenCalledWith({
        conditions: [
            {
                condition: {
                    parameter: 'asdf',
                    value: 'jklö',
                },
                type: 'query_string',
            },
        ],
        frequency: 2,
        title: 'Rule 11',
    });
});

test('Show error if empty fields are confirmed', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    render(<RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />);

    expect(screen.queryByText('sulu_admin.error_required')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(screen.getAllByText('sulu_admin.error_required')).toHaveLength(2);
    expect(confirmSpy).not.toHaveBeenCalled();
});

test('Call onClose callback when overlay is closed', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    render(<RuleOverlay onClose={closeSpy} onConfirm={jest.fn()} open={true} value={undefined} />);

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(closeSpy).toHaveBeenCalledWith();
});
