// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import RadioGroup from '../RadioGroup';
import Radio from '../Radio';

test('The component should render', () => {
    const {container} = render(
        <RadioGroup className="my-group" value="1">
            <Radio value="1" />
            <Radio value="2" />
            <Radio value="3" />
        </RadioGroup>
    );
    expect(container).toMatchSnapshot();
});

test('The component should check the correct radio', () => {
    render(
        <RadioGroup value="1">
            <Radio value="1" />
            <Radio value="2" />
            <Radio value="3" />
        </RadioGroup>
    );

    const radioGroup = [
        screen.queryByDisplayValue('1'),
        screen.queryByDisplayValue('2'),
        screen.queryByDisplayValue('3'),
    ];

    expect(radioGroup[0]).toBeChecked();
    expect(radioGroup[1]).not.toBeChecked();
    expect(radioGroup[2]).not.toBeChecked();
});

test('The component should pass the disabled state to the radios', () => {
    render(
        <RadioGroup disabled={true} value="1">
            <Radio value="1" />
            <Radio value="2" />
            <Radio value="3" />
        </RadioGroup>
    );

    const radioGroup = [
        screen.queryByDisplayValue('1'),
        screen.queryByDisplayValue('2'),
        screen.queryByDisplayValue('3'),
    ];

    expect(radioGroup[0]).toBeDisabled();
    expect(radioGroup[1]).toBeDisabled();
    expect(radioGroup[2]).toBeDisabled();
});

test('The component should pass the change callback to the radios', async() => {
    const onChange = jest.fn();
    render(
        <RadioGroup onChange={onChange} value="1">
            <Radio value="1" />
            <Radio value="2" />
            <Radio value="3" />
        </RadioGroup>
    );

    const radioGroup = [
        screen.queryByDisplayValue('1'),
        screen.queryByDisplayValue('2'),
        screen.queryByDisplayValue('3'),
    ];

    await userEvent.click(radioGroup[0]);
    await userEvent.click(radioGroup[1]);
    await userEvent.click(radioGroup[2]);

    expect(onChange).toHaveBeenCalledTimes(3);
});
