// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import CheckboxGroup from '../CheckboxGroup';
import Checkbox from '../Checkbox';

test('The component should render', () => {
    expect(render(
        <CheckboxGroup className="test" onChange={jest.fn()} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    )).toMatchSnapshot();
});

test('The component should render disabled', () => {
    expect(render(
        <CheckboxGroup disabled={true} onChange={jest.fn()} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    )).toMatchSnapshot();
});

test('The component should call onChange handler when checkboxes are clicked', () => {
    const changeSpy = jest.fn();

    const checkboxGroup = mount(
        <CheckboxGroup onChange={changeSpy} values={['value-2', 'value-3']}>
            <Checkbox value="value-1">Value 1</Checkbox>
            <Checkbox value="value-2">Value 2</Checkbox>
            <Checkbox value="value-3">Value 3</Checkbox>
        </CheckboxGroup>
    );

    expect(changeSpy).not.toBeCalled();

    checkboxGroup.find('Checkbox[value="value-1"] input').prop('onChange')({currentTarget: {checked: true}});
    expect(changeSpy).toHaveBeenLastCalledWith(['value-2', 'value-3', 'value-1']);

    checkboxGroup.find('Checkbox[value="value-3"] input').prop('onChange')({currentTarget: {checked: false}});
    expect(changeSpy).toHaveBeenLastCalledWith(['value-2']);
});
