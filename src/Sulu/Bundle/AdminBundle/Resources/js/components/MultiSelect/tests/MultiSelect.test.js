/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import {GenericSelect, Divider, Option} from '../../Select';
import MultiSelect from '../MultiSelect';

jest.mock('../../Select/GenericSelect');

test('The component should render a generic select', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect label="My label" onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    expect(select.node.type).toBe(GenericSelect);
});

test('The component should pass the correct label', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect label="My label" onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const label = select.find(GenericSelect).props().labelText;
    expect(label).toBe('My label');
});

test('The component should select the correct option', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect values={['option-1', 'option-2']} label="My label" onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const isOptionSelected = select.find(GenericSelect).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 'option-1'}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-2'}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-3'}})).toBe(false);
});

test('The component should trigger the change callback on select with an added value', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <MultiSelect values={['option-1', 'option-2']} label="My label" onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    select.find(GenericSelect).props().onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1', 'option-2', 'option-3']);
});

test('The component should trigger the change callback on select with a removed value', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <MultiSelect values={['option-1', 'option-2']} label="My label" onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    select.find(GenericSelect).props().onSelect('option-2');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1']);
});
