// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Switch from '../Switch';

test('The component should render in unchecked state', () => {
    const {container} = render(<Switch checked={false} />);
    expect(container).toMatchSnapshot();
});

test('The component should render in checked state', () => {
    const {container} = render(<Switch checked={true} />);
    expect(container).toMatchSnapshot();
});

test('The component should render with class', () => {
    const {container} = render(<Switch checked={false} className="my-class" />);
    expect(container).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const {container} = render(<Switch disabled={true} />);
    expect(container).toMatchSnapshot();
});

test('The component should render with name', () => {
    const {container} = render(<Switch checked={false} name="my-name" />);
    expect(container).toMatchSnapshot();
});

test('The component should render without a label container', () => {
    const {container} = render(<Switch checked={false} name="my-name" />);
    expect(container).toMatchSnapshot();
});

test('The component should render with radio type', () => {
    const {container} = render(<Switch checked={false} className="my-class" type="radio" />);
    expect(container).toMatchSnapshot();
});

test('A click on the checkbox should trigger the change callback', () => {
    const onChangeSpy = jest.fn();
    const {rerender} = render(<Switch checked={false} onChange={onChangeSpy} />);

    fireEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toBeCalledWith(true, undefined);

    rerender(<Switch checked={true} onChange={onChangeSpy} />);

    fireEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toBeCalledWith(false, undefined);
});

test('A click on the checkbox should trigger the change callback with the value', () => {
    const onChangeSpy = jest.fn();
    const {rerender} = render(<Switch checked={false} onChange={onChangeSpy} value="my-value" />);

    fireEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toHaveBeenCalledWith(true, 'my-value');

    rerender(<Switch checked={true} onChange={onChangeSpy} value="my-value" />);

    fireEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toHaveBeenCalledWith(false, 'my-value');
});

test('A click on the checkbox should stop the further propagation of the DOM event', () => {
    const stopPropagationSpy = jest.fn();
    render(<Switch />);

    fireEvent.click(screen.queryByRole('checkbox'), {stopPropagation: stopPropagationSpy});
    expect(stopPropagationSpy).toBeCalledWith();
});
