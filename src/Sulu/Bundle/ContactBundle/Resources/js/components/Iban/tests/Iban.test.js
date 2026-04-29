// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Iban from '../Iban';

test('Iban should render', () => {
    const onChange = jest.fn();
    const {asFragment} = render(<Iban onChange={onChange} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render with placeholder', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} placeholder="My placeholder" value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render with value', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render when disabled', () => {
    const {asFragment} = render(<Iban disabled={true} onChange={jest.fn()} value="AT61 1904 3002 3457 3201" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should render error', () => {
    const {asFragment} = render(<Iban onChange={jest.fn()} valid={false} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Iban should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Iban onBlur={onBlur} onChange={onChange} value={null} />);
    const input = screen.getByRole('textbox');

    // provide invalid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'xxx'}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith('xxx');
    expect(onBlur).toBeCalled();

    // provide one more invalid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'abc'}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith('abc');
    expect(onBlur).toBeCalled();

    // now add a valid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'AT611904300234573201'}});
    fireEvent.blur(input);
    expect(onChange).toBeCalledWith('AT611904300234573201');
    expect(onBlur).toBeCalled();

    expect(onBlur).toHaveBeenCalledTimes(3);
});
