// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Backdrop from '../Backdrop';

test('The component should render', () => {
    const {container} = render(<Backdrop />);
    expect(container).toMatchSnapshot();
});

test('The component should call a function when clicked', async() => {
    const onClickSpy = jest.fn();
    render(<Backdrop onClick={onClickSpy} />);
    const backdrop = screen.queryByTestId('backdrop');

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    await userEvent.click(backdrop);
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
