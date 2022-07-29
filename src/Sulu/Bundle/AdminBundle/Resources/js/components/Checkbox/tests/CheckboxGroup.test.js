// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import CheckboxGroup from '../CheckboxGroup';
import Checkbox from '../Checkbox';

test('The component should render', () => {
    const {container} = render(
        <CheckboxGroup className="test" onChange={jest.fn()} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    );

    expect(container).toMatchSnapshot();
});

test('The component should render disabled', () => {
    const {container} = render(
        <CheckboxGroup disabled={true} onChange={jest.fn()} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    );

    expect(container).toMatchSnapshot();
});

test('The component should call onChange handler when checkboxes are clicked', () => {
    const changeSpy = jest.fn();

    render(
        <CheckboxGroup onChange={changeSpy} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    );

    expect(changeSpy).not.toBeCalled();

    const checkbox1 = screen.getByDisplayValue('value-1');
    const checkbox3 = screen.getByDisplayValue('value-3');

    fireEvent.click(checkbox1);
    expect(changeSpy).toHaveBeenLastCalledWith(['value-2', 'value-3', 'value-1']);

    fireEvent.click(checkbox3);
    expect(changeSpy).toHaveBeenLastCalledWith(['value-2']);
});
