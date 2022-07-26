// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Button from '../Button';

test('Should render the button with icon', () => {
    expect(render(<Button icon="su-plus">Add something</Button>)).toMatchSnapshot();
});

test('Should render with skin primary', () => {
    expect(render(<Button skin="primary" />)).toMatchSnapshot();
});

test('Should render with skin secondary', () => {
    expect(render(<Button skin="secondary" />)).toMatchSnapshot();
});

test('should render disabled with skin secondary', () => {
    expect(render(<Button disabled={true} skin="secondary" />)).toMatchSnapshot();
});

test('Should render with skin link', () => {
    expect(render(<Button skin="link" />)).toMatchSnapshot();
});

test('Should render with skin text', () => {
    expect(render(<Button skin="text" />)).toMatchSnapshot();
});

test('Should render with skin icon', () => {
    expect(render(<Button skin="icon" />)).toMatchSnapshot();
});

test('Should render with skin icon and text', () => {
    expect(render(<Button skin="icon">Icon Text</Button>)).toMatchSnapshot();
});

test('Should render with skin icon and active', () => {
    expect(render(<Button active={true} skin="icon" />)).toMatchSnapshot();
});

test('Should render with skin icon and dropdown icon', () => {
    expect(render(<Button showDropdownIcon={true} skin="icon" />)).toMatchSnapshot();
});

test('Should render with skin primary and dropdown icon', () => {
    expect(render(<Button showDropdownIcon={true} skin="primary" />)).toMatchSnapshot();
});

test('Should render with skin secondary and dropdown icon', () => {
    expect(render(<Button showDropdownIcon={true} skin="secondary" />)).toMatchSnapshot();
});

test('Should render with skin link and dropdown icon', () => {
    expect(render(<Button showDropdownIcon={true} skin="link" />)).toMatchSnapshot();
});

test('Should call the callback on click', () => {
    const preventDefaultSpy = jest.fn();
    const onClick = jest.fn();
    render(<Button onClick={onClick} skin="primary" />);

    const button = screen.queryByRole('button');
    userEvent.click(button, {preventDefault: preventDefaultSpy});

    expect(preventDefaultSpy).toBeCalled();
    expect(onClick).toBeCalled();
});
