// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Bic should trigger callbacks correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const onBlur = jest.fn();
    const {rerender} = render(<Bic onBlur={onBlur} onChange={onChange} value={null} />);
    onChange.mockImplementation((value) => {
        rerender(<Bic onBlur={onBlur} onChange={onChange} value={value} />);
    });
    const input = screen.getByRole('textbox');

    // provide invalid value
    await user.type(input, 'xxx');
    await user.tab();
    expect(onChange).toHaveBeenLastCalledWith('xxx');
    expect(onBlur).toHaveBeenCalled();

    // provide one more invalid value
    await user.clear(input);
    await user.type(input, 'BBBBCCLLX');
    await user.tab();
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLLX');
    expect(onBlur).toHaveBeenCalled();

    // now add a valid value
    await user.clear(input);
    await user.type(input, 'BBBBCCLLXXX');
    await user.tab();
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLLXXX');
    expect(onBlur).toHaveBeenCalled();

    // provide one more valid value
    await user.clear(input);
    await user.type(input, 'BBBBCCLL');
    await user.tab();
    expect(onChange).toHaveBeenLastCalledWith('BBBBCCLL');
    expect(onBlur).toHaveBeenCalled();

    expect(onBlur).toHaveBeenCalledTimes(4);
});
