// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Option from '../Option';

jest.mock('../../../utils/DOM/afterElementsRendered');

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

test('A click on the component should fire the callback', () => {
    const clickSpy = jest.fn();
    render(<Option onClick={clickSpy}>My option</Option>);

    const button = screen.queryByText('My option');
    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.click(button);

    expect(clickSpy).toBeCalled();
});

test('A hover on the component should fire the callback', () => {
    const requestFocusSpy = jest.fn();
    render(<Option requestFocus={requestFocusSpy}>My option</Option>);

    const item = screen.queryByRole('listitem');
    userEvent.hover(item);

    expect(requestFocusSpy).toBeCalled();
});
