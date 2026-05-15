// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Input} from 'sulu-admin-bundle/components';
import KeyValue from '../../ruleTypes/KeyValue';

jest.mock('sulu-admin-bundle/components', () => ({
    Input: jest.fn(() => null),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function getInputProps(index: number) {
    const calls = (Input: any).mock.calls;
    return calls[index][0];
}

test('Render a KeyValue RuleType', () => {
    const options = {
        keyPlaceholder: 'key',
        valuePlaceholder: 'value',
    };

    render(<KeyValue onChange={jest.fn()} options={options} value={{}} />);

    expect((Input: any).mock.calls).toHaveLength(2);
    expect(getInputProps(0).placeholder).toEqual('key');
    expect(getInputProps(1).placeholder).toEqual('value');
});

test.each([
    ['test1', 'value1', {}, {test1: 'value1'}],
    ['test2', 'value2', {test2: 'value1'}, {test2: 'value2'}],
    ['test2', 'value2', {test1: 'value1'}, {test1: 'value1', test2: 'value2'}],
])('Call onChange handler when value is changed for "%s" to "%s"', (valueName, value, oldValue, result) => {
    const changeSpy = jest.fn();

    const options = {
        valueName,
    };

    render(<KeyValue onChange={changeSpy} options={options} value={oldValue} />);
    getInputProps(1).onChange(value);

    expect(changeSpy).toBeCalledWith(result);
});

test.each([
    ['test1', 'key1', {}, {test1: 'key1'}],
    ['test2', 'key2', {test2: 'key1'}, {test2: 'key2'}],
    ['test2', 'key2', {test1: 'key1'}, {test1: 'key1', test2: 'key2'}],
])('Call onChange handler when key is changed for "%s" to "%s"', (keyName, key, oldValue, result) => {
    const changeSpy = jest.fn();

    const options = {
        keyName,
    };

    render(<KeyValue onChange={changeSpy} options={options} value={oldValue} />);
    getInputProps(0).onChange(key);

    expect(changeSpy).toBeCalledWith(result);
});
