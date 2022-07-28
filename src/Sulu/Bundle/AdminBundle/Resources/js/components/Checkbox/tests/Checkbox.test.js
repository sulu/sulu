// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import Checkbox from '../Checkbox';

test('The component should render in light skin', () => {
    const {container} = render(<Checkbox skin="light" />);
    expect(container).toMatchSnapshot();
});

test('The component should render in dark skin', () => {
    const {container} = render(<Checkbox skin="dark" />);
    expect(container).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const {container} = render(<Checkbox disabled={true} />);
    expect(container).toMatchSnapshot();
});

test('The component passes the props correctly to the generic checkbox', () => {
    const onChange = jest.fn();

    render(
        <Checkbox
            checked={true}
            disabled={true}
            name="my-name"
            onChange={onChange}
            value="my-value"
        >
            My label
        </Checkbox>
    );

    const input = screen.getByDisplayValue('my-value');

    expect(input).toBeInTheDocument();
    expect(input).toBeDisabled();
    expect(input.name).toEqual('my-name');
    expect(input).toBeChecked();
    expect(screen.getByText('My label')).toBeInTheDocument();
});
