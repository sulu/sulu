// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Button} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import ConditionList from '../ConditionList';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));
jest.mock('sulu-admin-bundle/components', () => ({
    Button: jest.fn(() => null),
}));

test('Render an empty ConditionList', () => {
    const value = [];
    render(<ConditionList onChange={jest.fn()} value={value} />);

    const buttonProps: any = getLatestMockProps((Button: any));
    expect(buttonProps.icon).toEqual('su-plus');
});

test('Add a new Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
    ];

    const changeSpy = jest.fn();

    const conditionList = new ConditionList({onChange: changeSpy, value});
    conditionList.handleAddClick();

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {}, type: undefined}]);
});

test('Edit an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    const conditionList = new ConditionList({onChange: changeSpy, value});
    conditionList.handleChange({condition: {test: 'value'}, type: 'test'}, 1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {test: 'value'}, type: 'test'}]);
});

test('Remove an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    const conditionList = new ConditionList({onChange: changeSpy, value});
    conditionList.handleRemove(1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}]);
});
