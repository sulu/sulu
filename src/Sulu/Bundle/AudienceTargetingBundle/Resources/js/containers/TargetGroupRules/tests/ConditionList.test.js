// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import ConditionList from '../ConditionList';
import Condition from '../Condition';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../Condition', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function getConditionProps(index: number) {
    const calls = (Condition: any).mock.calls;
    return calls[index][0];
}

test('Render an empty ConditionList', () => {
    const value = [];

    render(<ConditionList onChange={jest.fn()} value={value} />);

    expect((Condition: any).mock.calls).toHaveLength(0);
    expect(screen.getByRole('button', {name: /sulu_audience_targeting\.add_condition/})).toBeInTheDocument();
});

test('Add a new Condition', async() => {
    const user = userEvent.setup();
    const value = [
        {condition: {}, type: 'browser'},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);
    await user.click(screen.getByRole('button', {name: /sulu_audience_targeting\.add_condition/}));

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {}, type: undefined}]);
});

test('Edit an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);
    getConditionProps(1).onChange({condition: {test: 'value'}, type: 'test'}, 1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {test: 'value'}, type: 'test'}]);
});

test('Remove an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    render(<ConditionList onChange={changeSpy} value={value} />);
    getConditionProps(1).onRemove(1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}]);
});
