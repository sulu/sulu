// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import Input from '../../ruleTypes/Input';

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass the change callback for "%s" with a value of "%s"', async(name, value) => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(bindValueToOnChange(<Input onChange={changeSpy} options={{name}} value={{}} />));

    await user.type(screen.getByRole('textbox'), value);

    expect(changeSpy).toHaveBeenLastCalledWith({[name]: value});
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass value for "%s" with a value of "%s" correctly to Input', (name, value) => {
    render(<Input onChange={jest.fn()} options={{name}} value={{[name]: value}} />);

    expect(screen.getByRole('textbox')).toHaveValue(value);
});
