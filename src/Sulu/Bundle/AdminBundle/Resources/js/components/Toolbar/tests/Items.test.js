/* eslint-disable flowtype/require-valid-file-annotation */
import {render, screen} from '@testing-library/react';
import React from 'react';
import debounce from 'debounce';
import Items from '../Items';
import Button from '../Button';

const clickSpy = jest.fn();

test('Render items', () => {
    const {asFragment} = render(<Items />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render items with children', () => {
    const {asFragment} = render(<Items><Button onClick={clickSpy}>Test</Button></Items>);

    expect(asFragment()).toMatchSnapshot();
});

test('Resize div should call callback', () => {
    const resizeFunction = jest.fn();
    debounce.mockReturnValue(resizeFunction);

    render(
        <Items>
            <Button>Test</Button>
        </Items>
    );

    expect(ResizeObserver).toBeCalledWith(resizeFunction);
    const childRef = screen.getByRole('list');
    const parentRef = childRef.parentElement;
    expect(ResizeObserver.mock.instances[0].observe).toBeCalledWith(parentRef);

    if (!(parentRef instanceof HTMLElement) || !(childRef instanceof HTMLElement)) {
        throw new Error('Expected toolbar items refs');
    }

    Object.defineProperty(childRef, 'offsetWidth', {
        value: 50,
        configurable: true,
    });
    Object.defineProperty(parentRef, 'offsetWidth', {
        value: 40,
        configurable: true,
    });

    debounce.mock.calls[0][0]();

    expect(screen.queryByText('Test')).not.toBeInTheDocument();
});

test('ResizeObserver.disconnect should be called before component unmount', () => {
    const {unmount} = render(
        <Items>
            <Button>Test</Button>
        </Items>
    );

    unmount();

    expect(ResizeObserver.mock.instances[0].disconnect).toBeCalled();
});
