// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Bic from '../Bic';

test('Bic should render', () => {
    const onChange = jest.fn();
    const {asFragment} = render(<Bic onChange={onChange} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bic should render with placeholder', () => {
    const {asFragment} = render(<Bic onChange={jest.fn()} placeholder="My placeholder" value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bic should render with value', () => {
    const {asFragment} = render(<Bic onChange={jest.fn()} value="BBBBCCLLXXX" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bic should render when disabled', () => {
    const {asFragment} = render(<Bic disabled={true} onChange={jest.fn()} value="BBBBCCLLXXX" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bic should render error', () => {
    const {asFragment} = render(<Bic onChange={jest.fn()} valid={false} value={null} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Bic should trigger callbacks correctly', () => {
    const onChange = jest.fn();
    const onBlur = jest.fn();
    render(<Bic onBlur={onBlur} onChange={onChange} value={null} />);
    const input = screen.getByRole('textbox');

    // provide invalid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'xxx'}});
    fireEvent.blur(input);
    expect(onChange).toHaveBeenLastCalledWith('xxx');
    expect(onBlur).toHaveBeenCalled();

    // provide one more invalid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'BBBBCCLLX'}});
    fireEvent.blur(input);
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLLX');
    expect(onBlur).toHaveBeenCalled();

    // now add a valid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'BBBBCCLLXXX'}});
    fireEvent.blur(input);
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLLXXX');
    expect(onBlur).toHaveBeenCalled();

    // provide one more valid value
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(input, {target: {value: 'BBBBCCLL'}});
    fireEvent.blur(input);
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLL');
    expect(onBlur).toHaveBeenCalled();

    expect(onBlur).toHaveBeenCalledTimes(4);
});
