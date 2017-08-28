/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import GenericSelect from '../../GenericSelect';
import MultiSelect, {Divider, Option} from '../../MultiSelect';

jest.mock('../../GenericSelect');

test('The component should render a generic select', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    expect(select.node.type).toBe(GenericSelect);
});

test('The component should pass the correct display value if nothing is selected', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(GenericSelect).props().displayValue;
    expect(displayValue).toBe('None selected');
});

test('The component should pass the correct display value if everything is selected', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect
            values={['option-1', 'option-2', 'option-3']}
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(GenericSelect).props().displayValue;
    expect(displayValue).toBe('All selected');
});

test('The component should pass the correct display value if some options are selected', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect
            values={['option-1', 'option-2']}
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChange}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(GenericSelect).props().displayValue;
    expect(displayValue).toBe('Option 1, Option 2');
});

test('The component should select the correct option', () => {
    const onChange = () => {};
    const select = shallow(
        <MultiSelect
            values={['option-1', 'option-2']}
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChange}>
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
        <MultiSelect
            values={['option-1', 'option-2']}
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChangeSpy}>
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
        <MultiSelect
            values={['option-1', 'option-2']}
            noneSelectedText="None selected"
            allSelectedText="All selected"
            onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    select.find(GenericSelect).props().onSelect('option-2');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1']);
});
