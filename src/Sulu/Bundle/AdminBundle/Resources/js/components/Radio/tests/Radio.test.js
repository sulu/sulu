// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Radio from '../Radio';

test('The component should render in light skin', () => {
    const {container} = render(<Radio skin="light" />);
    expect(container).toMatchSnapshot();
});

test('The component should render in dark skin', () => {
    const {container} = render(<Radio skin="dark" />);
    expect(container).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const {container} = render(<Radio disabled={true} />);
    expect(container).toMatchSnapshot();
});

test('The component pass the props correctly to the generic checkbox', () => {
    render(
        <Radio
            checked={true}
            disabled={true}
            name="my-name"
            value="my-value"
        >
            My label
        </Radio>
    );

    const checkbox = screen.queryByDisplayValue('my-value');

    expect(checkbox.value).toEqual('my-value');
    expect(checkbox).toHaveAttribute('name', 'my-name');
    expect(screen.getByText('My label')).toBeInTheDocument();
    expect(checkbox).toBeChecked();
    expect(checkbox).toBeDisabled();
});

test('The component pass the the value to the change callback', () => {
    const onChange = jest.fn();
    render(<Radio onChange={onChange} value="my-value">My label</Radio>);

    const checkbox = screen.queryByDisplayValue('my-value');
    fireEvent.click(checkbox);

    expect(onChange).toHaveBeenCalledWith('my-value');
});
