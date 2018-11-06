/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Select from '../../Select';
import SingleSelect from '../../SingleSelect';

const Option = SingleSelect.Option;
const Divider = SingleSelect.Divider;

jest.mock('../../Select');
jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render a generic select', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    expect(select.getElement().type).toBe(Select);
});

test('The component should render a select with dark skin', () => {
    const select = shallow(
        <SingleSelect skin="dark">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    expect(select.render()).toMatchSnapshot();
});

test('The component should render a select that is disabled', () => {
    const select = shallow(
        <SingleSelect disabled={true} skin="dark">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    expect(select.render()).toMatchSnapshot();
});

test('The component should return the default displayValue if no valueless option is present', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('sulu_admin.please_choose');
});

test('The component should return the content of the last valueless option as default displayValue', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option>Option without value 1</Option>
            <Option>Option without value 2</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option without value 2');
});

test('The component should return undefined as value if a valueless option is selected', () => {
    const select = shallow(
        <SingleSelect>
            <Option value="option-1">Option 1</Option>
            <Option>Option without value 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const value = select.find(Select).props().value;
    expect(value).toBe(undefined);
});

test('The component should return the correct displayValue', () => {
    const select = shallow(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option 2');
});

test('The component should return the correct displayValue and do not care if string or number', () => {
    const select = shallow(
        <SingleSelect value={2}>
            <Option value="1">Option 1</Option>
            <Option value="2">Option 2</Option>
            <Divider />
            <Option value="3">Option 3</Option>
        </SingleSelect>
    );
    const displayValue = select.find(Select).props().displayValue;
    expect(displayValue).toBe('Option 2');
});

test('The component should select the correct option', () => {
    const select = shallow(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    const isOptionSelected = select.find(Select).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 'option-1', disabled: false}})).toBe(false);
    expect(isOptionSelected({props: {value: 'option-2', disabled: false}})).toBe(true);
    expect(isOptionSelected({props: {value: 'option-3', disabled: false}})).toBe(false);
});

test('The component should also select the option with the value 0', () => {
    const select = shallow(
        <SingleSelect value={0}>
            <Option value={0}>Option 1</Option>
            <Option value={1}>Option 2</Option>
            <Divider />
            <Option value={2}>Option 3</Option>
        </SingleSelect>
    );
    const isOptionSelected = select.find(Select).props().isOptionSelected;
    expect(isOptionSelected({props: {value: 0}})).toBe(true);
    expect(isOptionSelected({props: {value: 1}})).toBe(false);
    expect(isOptionSelected({props: {value: 2}})).toBe(false);
});

test('The component should trigger the change callback on select', () => {
    const onChangeSpy = jest.fn();
    const select = shallow(
        <SingleSelect onChange={onChangeSpy} value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );
    select.find(Select).props().onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith('option-3');
});
