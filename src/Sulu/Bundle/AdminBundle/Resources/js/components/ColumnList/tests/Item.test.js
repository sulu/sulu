// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Item from '../Item';

test('Should render item as not selected by default', () => {
    const {asFragment} = render(<Item id={1} order={1}>Test</Item>);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render item as selected', () => {
    const {asFragment} = render(<Item id={1} order={2} selected={true}>Test</Item>);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render item as disabled', () => {
    const {asFragment} = render(<Item disabled={true} id={1} order={3}>Test</Item>);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render item with indicators', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];

    const {asFragment} = render(<Item id={2} indicators={indicators} order={4}>Test with indicators</Item>);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render item with order input', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];

    const {asFragment} = render(
        <Item id={2} indicators={indicators} order={4} showOrderField={true}>Test with indicators</Item>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should call onDoubleClick', () => {
    const doubleClickSpy = jest.fn();

    render(<Item id={2} onDoubleClick={doubleClickSpy}>Test with indicators</Item>);
    fireEvent.doubleClick(screen.getByRole('button'));

    expect(doubleClickSpy).toBeCalled();
});

test('Should not call onDoubleClick if order field is shown', () => {
    const doubleClickSpy = jest.fn();

    render(<Item id={2} onDoubleClick={doubleClickSpy} showOrderField={true}>Test with indicators</Item>);
    fireEvent.doubleClick(screen.getByRole('button'));

    expect(doubleClickSpy).not.toBeCalled();
});

test('Should call onOrderChange callback when order has changed', async() => {
    const orderChangePromise = Promise.resolve(true);
    const orderChangeSpy = jest.fn().mockReturnValue(orderChangePromise);
    const user = userEvent.setup();

    render(
        <Item id={2} onOrderChange={orderChangeSpy} order={4} showOrderField={true}>Test with indicators</Item>
    );
    const input = screen.getByRole('textbox');

    await user.clear(input);
    await user.type(input, '5');
    fireEvent.blur(input);
    expect(orderChangeSpy).toBeCalledWith(2, 5);

    expect(input).toHaveValue('5');

    await orderChangePromise;
    expect(input).toHaveValue('5');
});

test('Should call onOrderChange callback when order has changed and reset order if cancelled', async() => {
    const orderChangePromise = Promise.resolve(false);
    const orderChangeSpy = jest.fn().mockReturnValue(orderChangePromise);
    const user = userEvent.setup();

    render(
        <Item id={2} onOrderChange={orderChangeSpy} order={4} showOrderField={true}>Test with indicators</Item>
    );
    const input = screen.getByRole('textbox');

    await user.clear(input);
    await user.type(input, '5');
    fireEvent.blur(input);
    expect(orderChangeSpy).toBeCalledWith(2, 5);

    expect(input).toHaveValue('5');

    await orderChangePromise;
    expect(input).toHaveValue('4');
});

test('Should call onOrderChange callback when order has changed after pressing enter', async() => {
    const orderChangeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Item id={2} onOrderChange={orderChangeSpy} order={4} showOrderField={true}>Test with indicators</Item>
    );
    const input = screen.getByRole('textbox');
    const blurSpy = jest.spyOn(HTMLInputElement.prototype, 'blur').mockImplementation(() => {});

    await user.type(input, '{enter}');

    expect(blurSpy).toBeCalledWith();
    blurSpy.mockRestore();
});

test('Should change order when item receives new props', async() => {
    const {rerender} = render(
        <Item id={2} order={4} showOrderField={true}>Test with indicators</Item>
    );
    const user = userEvent.setup();
    const input = screen.getByRole('textbox');

    expect(input).toHaveValue('4');

    await user.clear(input);
    await user.type(input, '5');
    expect(input).toHaveValue('5');

    rerender(<Item id={2} order={1} showOrderField={true}>Test with indicators</Item>);
    expect(input).toHaveValue('1');
});
