// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleSelect from '../../ruleTypes/SingleSelect';

jest.mock('sulu-admin-bundle/utils/Translator');

test.each([
    [[{id: 'firefox', name: 'Firefox'}]],
    [[{id: 'firefox', name: 'Firefox'}, {id: 'chrome', name: 'Chrome'}]],
    [[{id: 'ie', name: 'Internet Explorer'}, {id: 'firefox', name: 'Firefox'}]],
])('Option should be listed in Select #%#', async(options) => {
    const user = userEvent.setup();

    render(<SingleSelect onChange={jest.fn()} options={{options}} value={{}} />);

    await user.click(screen.getByLabelText('su-angle-down'));

    options.forEach((option) => {
        expect(screen.getByRole('button', {name: option.name})).toBeInTheDocument();
    });
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Call onChange for "%s" with a value of "%s"', async(name, value) => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <SingleSelect
            onChange={changeSpy}
            options={{name, options: [{id: value, name: value}]}}
            value={{}}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: value}));

    expect(changeSpy).toHaveBeenCalledWith({[name]: value});
});

test.each([
    ['name1', 'value1'],
    ['name2', 'value2'],
])('Display correct value for "%s" with a value of "%s"', (name, value) => {
    render(
        <SingleSelect
            onChange={jest.fn()}
            options={{name, options: [{id: value, name: value}]}}
            value={{[name]: value}}
        />
    );

    expect(screen.getByRole('button', {name: new RegExp(value)})).toBeInTheDocument();
});
