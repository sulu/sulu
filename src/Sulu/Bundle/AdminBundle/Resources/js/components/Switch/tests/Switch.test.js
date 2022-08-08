// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('A click on the checkbox should trigger the change callback', async() => {
    const onChangeSpy = jest.fn();
    const {rerender} = render(<Switch checked={false} onChange={onChangeSpy} />);

    await userEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toBeCalledWith(true, undefined);

    rerender(<Switch checked={true} onChange={onChangeSpy} />);

    await userEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toBeCalledWith(false, undefined);
});

test('A click on the checkbox should trigger the change callback with the value', async() => {
    const onChangeSpy = jest.fn();
    const {rerender} = render(<Switch checked={false} onChange={onChangeSpy} value="my-value" />);

    await userEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toHaveBeenCalledWith(true, 'my-value');

    rerender(<Switch checked={true} onChange={onChangeSpy} value="my-value" />);

    await userEvent.click(screen.queryByRole('checkbox'));
    expect(onChangeSpy).toHaveBeenCalledWith(false, 'my-value');
});
