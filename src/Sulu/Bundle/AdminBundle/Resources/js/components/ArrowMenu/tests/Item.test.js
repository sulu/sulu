// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Item from '../Item';

test('Render default Item', () => {
    const {container} = render(<Item value="test">Test Item</Item>);

    expect(container).toMatchSnapshot();
});

test('Render active Item', () => {
    const {container} = render(<Item active={true} icon="fa-home" value="house">My House</Item>);

    expect(container).toMatchSnapshot();
});

test('Render disabled Item', () => {
    const {container} = render(<Item active={true} disabled={true} icon="fa-home" value="house">My Item</Item>);

    expect(container).toMatchSnapshot();
});

test('Clicking the left and right button inside the header should call the right handler', async () => {
    const clickHandler = jest.fn();
    render(<Item active={true} icon="fa-home" onClick={clickHandler} value="house">My House</Item>);

    const user = userEvent.setup();
    await user.click(screen.getByText('My House'));

    expect(clickHandler).toBeCalledWith('house');
});

test('Clicking the disabled Item should not call a handler', async () => {
    const clickHandler = jest.fn();
    render(<Item active={false} disabled={true} icon="fa-home" onClick={clickHandler} value="house">My House</Item>);

    const user = userEvent.setup();
    await user.click(screen.getByText('My House'));

    expect(clickHandler).not.toBeCalled();
});
