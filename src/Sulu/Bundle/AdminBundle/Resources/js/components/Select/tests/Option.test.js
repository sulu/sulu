// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Option from '../Option';

test('The component should render', () => {
    const {container} = render(<Option value="my-option">My option</Option>);
    expect(container).toMatchSnapshot();
});

test('The component should render in selected state', () => {
    const {container} = render(<Option selected={true} value="my-option">My option</Option>);
    expect(container).toMatchSnapshot();
});

test('The component should render with checkbox', () => {
    const {container} = render(<Option selectedVisualization="checkbox" value="my-option">My option</Option>);
    expect(container).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const {container} = render(<Option disabled={true} value="my-option">My option</Option>);
    expect(container).toMatchSnapshot();
});

test('A click on the component should fire the callback', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();
    render(<Option onClick={clickSpy}>My option</Option>);

    const button = screen.queryByText('My option');
    await user.click(button);

    expect(clickSpy).toHaveBeenCalled();
});

test('A hover on the component should fire the callback', async() => {
    const user = userEvent.setup();
    const requestFocusSpy = jest.fn();
    render(<Option requestFocus={requestFocusSpy}>My option</Option>);

    const item = screen.queryByRole('listitem');
    await user.hover(item);

    expect(requestFocusSpy).toHaveBeenCalled();
});
