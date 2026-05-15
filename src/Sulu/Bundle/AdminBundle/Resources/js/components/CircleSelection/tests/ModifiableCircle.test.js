// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import ModifiableCircle from '../ModifiableCircle';

function dispatchMouseDown(element: HTMLElement, pageX: number, pageY: number) {
    const mouseDownEvent = new MouseEvent('mousedown', {
        bubbles: true,
        cancelable: true,
        clientX: pageX,
        clientY: pageY,
    });

    Object.defineProperty(mouseDownEvent, 'pageX', {value: pageX});
    Object.defineProperty(mouseDownEvent, 'pageY', {value: pageY});

    element.dispatchEvent(mouseDownEvent);
}

test('The component should render', () => {
    const {asFragment} = render(<ModifiableCircle label="" left={10} radius={100} top={20} />);

    expect(asFragment()).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();

    render(<ModifiableCircle label="" onDoubleClick={clickSpy} radius={100} />);

    fireEvent.doubleClick(screen.getByRole('button'));

    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    render(<ModifiableCircle label="" onChange={changeSpy} radius={100} />);

    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    dispatchMouseDown(screen.getByRole('button'), 10, 20);
    windowListeners.mousemove({pageX: 15, pageY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 10, left: 5, radius: 0});

    windowListeners.mouseup();
    windowListeners.mousemove({pageX: 100, pageY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    render(<ModifiableCircle label="" onChange={changeSpy} radius={100} />);

    const circle = screen.getByRole('button');
    Object.defineProperty(circle, 'getBoundingClientRect', {
        value: () => ({
            left: 200,
            width: 200,
            top: 200,
            height: 200,
        }),
        configurable: true,
    });

    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    dispatchMouseDown(screen.getByRole('slider'), 0, 0);
    windowListeners.mousemove({clientX: 400, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, radius: 41.42135623730951});

    windowListeners.mouseup();
    windowListeners.mousemove({clientX: -10, clientY: 10});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
