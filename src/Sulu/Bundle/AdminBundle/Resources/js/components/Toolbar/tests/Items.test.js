/* eslint-disable flowtype/require-valid-file-annotation */
import {render, mount} from 'enzyme';
import React from 'react';
import debounce from 'debounce';
import Items from '../Items';
import Button from '../Button';

const clickSpy = jest.fn();

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
});

test('Render items', () => {
    expect(render(<Items />)).toMatchSnapshot();
});

test('Render items with children', () => {
    expect(render(<Items><Button onClick={clickSpy}>Test</Button></Items>)).toMatchSnapshot();
});

test('Resize div should call callback', () => {
    const resizeFunction = jest.fn();
    debounce.mockReturnValue(resizeFunction);

    const items = mount(
        <Items>
            <Button>Test</Button>
        </Items>
    );

    expect(ResizeObserver).toBeCalledWith(resizeFunction);
    expect(ResizeObserver.mock.instances[0].observe).toBeCalledWith(items.instance().parentRef);

    expect(items.instance().showText).toEqual(true);

    items.instance().childRef = {
        offsetWidth: 50,
    };

    items.instance().parentRef = {
        offsetWidth: 40,
    };

    debounce.mock.calls[0][0]();

    expect(items.instance().expandedWidth).toEqual(50);
    expect(items.instance().parentWidth).toEqual(40);
    expect(items.instance().showText).toEqual(false);
});
