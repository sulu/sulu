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
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const options = {
        valueName,
    };

    render(bindValueToOnChange(<KeyValue onChange={changeSpy} options={options} value={oldValue} />));

    await user.clear(screen.getAllByRole('textbox')[1]);
    await user.type(screen.getAllByRole('textbox')[1], value);

    expect(changeSpy).toHaveBeenLastCalledWith(result);
});

test.each([
    ['test1', 'key1', {}, {test1: 'key1'}],
    ['test2', 'key2', {test2: 'key1'}, {test2: 'key2'}],
    ['test2', 'key2', {test1: 'key1'}, {test1: 'key1', test2: 'key2'}],
])('Call onChange handler when key is changed for "%s" to "%s"', async(keyName, key, oldValue, result) => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const options = {
        keyName,
    };

    render(bindValueToOnChange(<KeyValue onChange={changeSpy} options={options} value={oldValue} />));

    await user.clear(screen.getAllByRole('textbox')[0]);
    await user.type(screen.getAllByRole('textbox')[0], key);

    expect(changeSpy).toHaveBeenLastCalledWith(result);
});
