/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from '@testing-library/react';
import React from 'react';
import debounce from 'debounce';
import Items from '../Items';
import Button from '../Button';

const clickSpy = jest.fn();

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

test('Render items', () => {
    const {container} = render(<Items />);
    expect(container).toMatchSnapshot();
});

test('Render items with children', () => {
    const {container} = render(<Items><Button onClick={clickSpy}>Test</Button></Items>);
    expect(container).toMatchSnapshot();
});

test('Resize div should call callback', () => {
    const resizeFunction = jest.fn();
    debounce.mockReturnValue(resizeFunction);

    const {container} = render(
        <Items>
            <Button>Test</Button>
        </Items>
    );

    // eslint-disable-next-line testing-library/no-container, testing-library/no-node-access
    const items = container.querySelector('.itemsContainer');

    expect(ResizeObserver).toBeCalledWith(resizeFunction);
    expect(ResizeObserver.mock.instances[0].observe).toBeCalledWith(items);
    expect(items).toHaveTextContent('Test');
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
