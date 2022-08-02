// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Backdrop from '../Backdrop';

test('The component should render', () => {
    const {container} = render(<Backdrop />);
    expect(container).toMatchSnapshot();
});

test('The component should call a function when clicked', () => {
    const onClickSpy = jest.fn();
    render(<Backdrop onClick={onClickSpy} />);
    const backdrop = screen.queryByTestId('backdrop');

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    fireEvent.click(backdrop);
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
