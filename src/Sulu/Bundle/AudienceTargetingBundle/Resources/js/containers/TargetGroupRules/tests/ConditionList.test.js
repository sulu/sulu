// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ConditionList from '../ConditionList';
import ruleRegistry from '../registries/ruleRegistry';
import ruleTypeRegistry from '../registries/ruleTypeRegistry';
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

test('Render an empty ConditionList', () => {
    const value = [];
    const {asFragment} = render(<ConditionList onChange={jest.fn()} value={value} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Add a new Condition', async() => {
    const user = userEvent.setup();
    const value = [
        {condition: {}, type: 'browser'},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting.add_condition/}));

    expect(changeSpy).toHaveBeenCalledWith([{condition: {}, type: 'browser'}, {condition: {}, type: undefined}]);
});

test('Edit an existing Condition', async() => {
    const user = userEvent.setup();
    const value = [
        {condition: {browser: 'firefox'}, type: 'browser'},
        {condition: {}, type: 'browser'},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: /sulu_admin.please_choose/}));
    await user.click(screen.getByRole('button', {name: 'Chrome'}));

    expect(changeSpy).toHaveBeenCalledWith([
        {condition: {browser: 'firefox'}, type: 'browser'},
        {condition: {browser: 'chrome'}, type: 'browser'},
    ]);
});

test('Remove an existing Condition', async() => {
    const user = userEvent.setup();
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);

    await user.click(screen.getAllByRole('button', {name: 'su-trash-alt'})[1]);

    expect(changeSpy).toHaveBeenCalledWith([{condition: {}, type: 'browser'}]);
});
