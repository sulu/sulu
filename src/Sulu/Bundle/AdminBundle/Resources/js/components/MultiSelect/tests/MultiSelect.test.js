// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Select from '../../Select';
import MultiSelect from '../../MultiSelect';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

const Option = MultiSelect.Option;
const Divider = MultiSelect.Divider;

jest.mock('../../Select', () => {
    const React = require('react');
    const Select: any = jest.fn(({children}) => <div data-testid="select">{children}</div>);

    Select.Action = jest.fn(({children}) => <div data-testid="action">{children}</div>);
    Select.Option = jest.fn(({children}) => <div data-testid="option">{children}</div>);
    Select.Divider = jest.fn(() => <div data-testid="divider" />);

    return Select;
});

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

const renderMultiSelect = (props = {}) => {
    const allProps = {
        allSelectedText: 'All selected',
        noneSelectedText: 'None selected',
        onChange: jest.fn(),
        values: [],
        ...props,
    };
    const view = render(
        <MultiSelect {...(allProps: any)}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
    const selectProps: any = getLatestMockProps((Select: any));

    return {
        ...view,
        selectProps,
    };
};

beforeEach(() => {
    ((Select: any): {mockClear: () => void}).mockClear();
});

test('The component should render a generic select', () => {
    const {container} = renderMultiSelect();

    expect(Select).toBeCalled();
    expect(container).toMatchSnapshot();
});

test('The component should pass the disabled value to the select component', () => {
    const {selectProps} = renderMultiSelect({disabled: true});

    expect(selectProps.disabled).toBe(true);
});

test('The component should pass the correct display value if nothing is selected', () => {
    const {selectProps} = renderMultiSelect();

    expect(selectProps.displayValue).toBe('None selected');
});

test('The component should pass the correct display value if everything is selected', () => {
    const {selectProps} = renderMultiSelect({values: ['option-1', 'option-2', 'option-3']});

    expect(selectProps.displayValue).toBe('All selected');
});

test('The component should pass the correct display value if some options are selected', () => {
    const {selectProps} = renderMultiSelect({values: ['option-1', 'option-2']});

    expect(selectProps.displayValue).toBe('Option 1, Option 2');
});

test('The component should select the correct option', () => {
    const {selectProps} = renderMultiSelect({values: ['option-1', 'option-2']});

    expect(selectProps.isOptionSelected({props: {value: 'option-1'}})).toBe(true);
    expect(selectProps.isOptionSelected({props: {value: 'option-2'}})).toBe(true);
    expect(selectProps.isOptionSelected({props: {value: 'option-3'}})).toBe(false);
});

test('The component should trigger the change callback on select with an added value', () => {
    const onChangeSpy = jest.fn();
    const {selectProps} = renderMultiSelect({
        onChange: onChangeSpy,
        values: ['option-1', 'option-2'],
    });

    selectProps.onSelect('option-3');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1', 'option-2', 'option-3']);
});

test('The component should trigger the change callback on select with a removed value', () => {
    const onChangeSpy = jest.fn();
    const {selectProps} = renderMultiSelect({
        onChange: onChangeSpy,
        values: ['option-1', 'option-2'],
    });

    selectProps.onSelect('option-2');
    expect(onChangeSpy).toHaveBeenCalledWith(['option-1']);
});

test('The component should trigger the close callback when the MultiSelect is closed', () => {
    const closeSpy = jest.fn();
    const {selectProps} = renderMultiSelect({
        onClose: closeSpy,
    });

    expect(closeSpy).not.toBeCalled();
    selectProps.onClose();
    expect(closeSpy).toBeCalled();
});
