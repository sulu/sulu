// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ruleRegistry from '../registries/ruleRegistry';
import ruleTypeRegistry from '../registries/ruleTypeRegistry';
import Condition from '../Condition';
import SingleSelect from '../ruleTypes/SingleSelect';

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
};

beforeEach(() => {
    ruleRegistry.setRules(rules);
    ruleTypeRegistry.add('select', SingleSelect);
});

afterEach(() => {
    ruleRegistry.clear();
    ruleTypeRegistry.clear();
});

test('Render a condition', () => {
    const value = {condition: {browser: 'firefox'}, type: 'browser'};
    const {asFragment} = render(<Condition index={1} onChange={jest.fn()} onRemove={jest.fn()} value={value} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Call onRemove callback if remove icon is clicked', async() => {
    const user = userEvent.setup();
    const removeSpy = jest.fn();
    const value = {condition: {}, type: undefined};

    render(<Condition index={5} onChange={jest.fn()} onRemove={removeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(removeSpy).toHaveBeenCalledWith(5);
});

test('Call onChange callback if rule type changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = {condition: {test: 'value'}, type: undefined};

    render(<Condition index={5} onChange={changeSpy} onRemove={jest.fn()} value={value} />);

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'Browser'}));

    expect(changeSpy).toHaveBeenCalledWith({condition: {test: 'value'}, type: 'browser'}, 5);
});

test('Call onChange callback if condition changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = {condition: {}, type: 'browser'};

    render(<Condition index={5} onChange={changeSpy} onRemove={jest.fn()} value={value} />);

    await user.click(screen.getAllByLabelText('su-angle-down')[1]);
    await user.click(screen.getByRole('button', {name: 'Firefox'}));

    expect(changeSpy).toHaveBeenCalledWith({condition: {browser: 'firefox'}, type: 'browser'}, 5);
});
