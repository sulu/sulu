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

// test('The component pass the props correctly to the generic checkbox', () => {
//     const {debug} = render(
//         <Radio
//             checked={true}
//             disabled={true}
//             name="my-name"
//             value="my-value"
//         >
//             My label
//         </Radio>
//     );
//     debug();

//     const checkbox = screen.queryByDisplayValue('my-value');

//     // expect(checkbox).toHaveValue('my-value');
//     expect(checkbox).toHaveAccessibleName('my-name');
//     expect(checkbox).toBeChecked();
//     expect(checkbox).toBeDisabled();
//     expect(screen.getByText('My label')).toBeInTheDocument();
// });

test('The component pass the the value to the change callback', () => {
    const onChange = jest.fn();
    render(<Radio onChange={onChange} value="my-value">My label</Radio>);

    const input = screen.queryByDisplayValue('my-value');
    fireEvent.click(input);

    expect(onChange).toHaveBeenCalledWith('my-value');
});
