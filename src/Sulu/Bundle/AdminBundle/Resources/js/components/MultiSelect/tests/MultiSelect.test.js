/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Select from '../../Select';
import MultiSelect from '../../MultiSelect';

const Option = MultiSelect.Option;
const Divider = MultiSelect.Divider;

jest.mock('../../Select');

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('The component should render a generic select', () => {
    const onChange = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChange}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    expect(select.getElement().type).toBe(Select);
});

test('The component should pass the correct display value if nothing is selected', () => {
    const onChange = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChange}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('None selected');
});

test('The component should pass the correct display value if everything is selected', () => {
    const onChange = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChange}
            values={['option-1', 'option-2', 'option-3']}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('All selected');
});

test('The component should pass the correct display value if some options are selected', () => {
    const onChange = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChange}
            values={['option-1', 'option-2']}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option 1, Option 2');
});

test('The component should select the correct option', () => {
    const onChange = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChange}
            values={['option-1', 'option-2']}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const isOptionSelected = select.find(Select).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 'option-1'}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-2'}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-3'}})).toBe(false);
});

test('The component should trigger the change callback on select with an added value', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChangeSpy}
            values={['option-1', 'option-2']}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    select.find(Select).props().onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1', 'option-2', 'option-3']);
});

test('The component should trigger the change callback on select with a removed value', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={onChangeSpy}
            values={['option-1', 'option-2']}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    select.find(Select).props().onSelect('option-2');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1']);
});
