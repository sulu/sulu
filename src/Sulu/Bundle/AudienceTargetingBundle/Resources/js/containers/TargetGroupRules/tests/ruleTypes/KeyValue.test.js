// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import KeyValue from '../../ruleTypes/KeyValue';

test('Render a KeyValue RuleType', () => {
    const options = {
        keyPlaceholder: 'key',
        valuePlaceholder: 'value',
    };

    const {asFragment} = render(<KeyValue onChange={jest.fn()} options={options} value={{}} />);

    expect(asFragment()).toMatchSnapshot();
});

test.each([
    ['test1', 'value1', {}, {test1: 'value1'}],
    ['test2', 'value2', {test2: 'value1'}, {test2: 'value2'}],
    ['test2', 'value2', {test1: 'value1'}, {test1: 'value1', test2: 'value2'}],
])('Call onChange handler when value is changed for "%s" to "%s"', async(valueName, value, oldValue, result) => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    const options = {
        valueName,
    };

    render(bindValueToOnChange(<KeyValue onChange={changeSpy} options={options} value={oldValue} />));
    const inputFields = screen.getAllByRole('textbox');
    const valueInput = inputFields[1];
    await user.clear(valueInput);
    await user.type(valueInput, value);

    expect(changeSpy).toBeCalledWith(result);
});

test.each([
    ['test1', 'key1', {}, {test1: 'key1'}],
    ['test2', 'key2', {test2: 'key1'}, {test2: 'key2'}],
    ['test2', 'key2', {test1: 'key1'}, {test1: 'key1', test2: 'key2'}],
])('Call onChange handler when key is changed for "%s" to "%s"', async(keyName, key, oldValue, result) => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    const options = {
        keyName,
    };

    render(bindValueToOnChange(<KeyValue onChange={changeSpy} options={options} value={oldValue} />));
    const [keyInput] = screen.getAllByRole('textbox');
    await user.clear(keyInput);
    await user.type(keyInput, key);

    expect(changeSpy).toBeCalledWith(result);
});
