// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Action from '../Action';

test('Render default Action', () => {
    const {container} = render(<Action onClick={jest.fn()}>My Action</Action>);

    expect(container).toMatchSnapshot();
});

test('Render Action with icon', () => {
    const {container} = render(<Action icon="su-display-default" onClick={jest.fn()}>My Action</Action>);

    expect(container).toMatchSnapshot();
});

test('Render disabled Action', () => {
    const {container} = render(<Action disabled={true} onClick={jest.fn()}>My Action</Action>);

    expect(container).toMatchSnapshot();
});

test('Clicking the Action should call the right handler', async() => {
    const clickHandler = jest.fn();
    render(<Action onClick={clickHandler}>My Action</Action>);

    const user = userEvent.setup();
    await user.click(screen.getByText('My Action'));

    expect(clickHandler).toBeCalledWith(undefined);
});

test('Clicking the Action should call the right handler with the passed value', async() => {
    const clickHandler = jest.fn();
    render(<Action onClick={clickHandler} value="test">My Action</Action>);

    const user = userEvent.setup();
    await user.click(screen.getByText('My Action'));

    expect(clickHandler).toBeCalledWith('test');
});

test('Clicking the disabled Action should not call a handler', async() => {
    const clickHandler = jest.fn();
    render(<Action disabled={true} onClick={clickHandler}>My Action</Action>);

    const user = userEvent.setup();
    await user.click(screen.getByText('My Action'));

    expect(clickHandler).not.toBeCalled();
});
