// @flow
import React from 'react';
import {createEvent, fireEvent, render, screen} from '@testing-library/react';
import Button from '../Button';

test('Should render the button with icon', () => {
    const {container} = render(<Button icon="su-plus">Add something</Button>);
    expect(container).toMatchSnapshot();
});

test('Should render with skin primary', () => {
    const {container} = render(<Button skin="primary" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin secondary', () => {
    const {container} = render(<Button skin="secondary" />);
    expect(container).toMatchSnapshot();
});

test('should render disabled with skin secondary', () => {
    const {container} = render(<Button disabled={true} skin="secondary" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin link', () => {
    const {container} = render(<Button skin="link" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin text', () => {
    const {container} = render(<Button skin="text" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin icon', () => {
    const {container} = render(<Button skin="icon" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin icon and text', () => {
    const {container} = render(<Button skin="icon">Icon Text</Button>);
    expect(container).toMatchSnapshot();
});

test('Should render with skin icon and active', () => {
    const {container} = render(<Button active={true} skin="icon" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin icon and dropdown icon', () => {
    const {container} = render(<Button showDropdownIcon={true} skin="icon" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin primary and dropdown icon', () => {
    const {container} = render( <Button showDropdownIcon={true} skin="primary" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin secondary and dropdown icon', () => {
    const {container} = render(<Button showDropdownIcon={true} skin="secondary" />);
    expect(container).toMatchSnapshot();
});

test('Should render with skin link and dropdown icon', () => {
    expect(render(<Button showDropdownIcon={true} skin="link" />)).toMatchSnapshot();
});

test('Should call the callback on click', () => {
    const onClick = jest.fn();
    render(<Button onClick={onClick} skin="primary" />);

    const button = screen.queryByRole('button');
    const onClickPreventDefault = createEvent.click(button);

    fireEvent(button, onClickPreventDefault);

    expect(onClickPreventDefault.defaultPrevented).toBe(true);
    expect(onClick).toBeCalled();
});

test('Should call the buttonRef callback correctly', () => {
    const buttonRefSpy = jest.fn();
    render(<Button buttonRef={buttonRefSpy} />);

    const button = screen.queryByRole('button');

    expect(buttonRefSpy).toBeCalledWith(button);
});
