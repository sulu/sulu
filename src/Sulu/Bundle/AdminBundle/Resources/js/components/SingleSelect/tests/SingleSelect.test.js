// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import SingleSelect from '../../SingleSelect';

const Option = SingleSelect.Option;
const Divider = SingleSelect.Divider;

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render a generic select', () => {
    const {container} = render(
        <SingleSelect value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(container).toMatchSnapshot();
});

test('The component should render a select with dark skin', () => {
    const {container} = render(
        <SingleSelect skin="dark" value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(container).toMatchSnapshot();
});

test('The component should show a disabled select that is disabled', () => {
    render(
        <SingleSelect disabled={true} skin="dark" value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    const input = screen.queryByRole('button');
    expect(input).toBeDisabled();
});

test('The component should return the default displayValue if no valueless option is present', () => {
    render(
        <SingleSelect value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/sulu_admin.please_choose/)).toBeInTheDocument();
});

test('The component should return the content of the last valueless option as default displayValue', () => {
    render(
        <SingleSelect value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option>Option without value 1</Option>
            <Option>Option without value 2</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/Option without value 2/)).toBeInTheDocument();
});

test('The component should return undefined as value if a valueless option is selected', () => {
    render(
        <SingleSelect value={undefined}>
            <Option value="option-1">Option 1</Option>
            <Option>Option without value 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    const input = screen.queryByRole('button');
    expect(input).toHaveValue('');
});

test('The component should return the correct displayValue', () => {
    render(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/Option 2/)).toBeInTheDocument();
});

test('The component should return the correct displayValue and do not care if string or number', () => {
    render(
        <SingleSelect value={2}>
            <Option value="1">Option 1</Option>
            <Option value="2">Option 2</Option>
            <Divider />
            <Option value="3">Option 3</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/Option 2/)).toBeInTheDocument();
});

test('The component should select the correct option', () => {
    render(
        <SingleSelect value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    expect(screen.queryByText(/Option 1/)).not.toBeInTheDocument();
    expect(screen.getByText(/Option 2/)).toBeInTheDocument();
    expect(screen.queryByText(/Option 3/)).not.toBeInTheDocument();
});

test('The component should select the correct option if value is undefined', () => {
    render(
        <SingleSelect value={undefined}>
            <Option value={undefined}>undefined</Option>
            <Divider />
            <Option value="value">Value</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/undefined/)).toBeInTheDocument();
    expect(screen.queryByText(/Value/)).not.toBeInTheDocument();
});

test('The component should also select the option with the value 0', () => {
    render(
        <SingleSelect value={0}>
            <Option value={0}>Option 1</Option>
            <Option value={1}>Option 2</Option>
            <Divider />
            <Option value={2}>Option 3</Option>
        </SingleSelect>
    );

    expect(screen.getByText(/Option 1/)).toBeInTheDocument();
    expect(screen.queryByText(/Option 2/)).not.toBeInTheDocument();
    expect(screen.queryByText(/Option 3/)).not.toBeInTheDocument();
});

test('The component should trigger the change callback on select', async() => {
    const onChangeSpy = jest.fn();
    render(
        <SingleSelect onChange={onChangeSpy} value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </SingleSelect>
    );

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryByText('Option 3'));
    expect(onChangeSpy).toHaveBeenCalledWith('option-3');
});
