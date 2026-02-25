/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import debounce from 'debounce';
import {getMockCallArg} from '../../../utils/TestHelper';
import Items from '../Items';
import Button from '../Button';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

beforeEach(() => {
    jest.clearAllMocks();
});

const setOffsetWidth = (element, width) => {
    Object.defineProperty(element, 'offsetWidth', {
        configurable: true,
        value: width,
        writable: true,
    });
};

test('Render items', () => {
    const {asFragment} = render(<Items />);
    const listItems = screen.queryAllByRole('listitem');

    expect(listItems).toHaveLength(0);
    expect(asFragment()).toMatchSnapshot();
});

test('Render items with children', () => {
    render(
        <Items>
            <Button onClick={jest.fn()}>Test</Button>
        </Items>
    );

    expect(screen.getByRole('button', {name: 'Test'})).toBeInTheDocument();
});

test('Resize div should call callback', () => {
    const resizeFunction = jest.fn();
    debounce.mockReturnValue(resizeFunction);

    render(
        <Items>
            <Button>Test</Button>
        </Items>
    );

    const childNode = screen.getByRole('list');
    const parentNode = childNode.parentElement;

    if (!parentNode || !childNode) {
        throw new Error('Expected parent and child elements to exist');
    }

    expect(ResizeObserver).toBeCalledWith(resizeFunction);
    expect(ResizeObserver.mock.instances[0].observe).toBeCalledWith(parentNode);
    expect(screen.getByRole('button', {name: 'Test'})).toBeInTheDocument();

    setOffsetWidth(childNode, 50);
    setOffsetWidth(parentNode, 40);

    act(() => {
        const setDimensions = getMockCallArg(debounce, 0, 0);
        setDimensions();
    });

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
