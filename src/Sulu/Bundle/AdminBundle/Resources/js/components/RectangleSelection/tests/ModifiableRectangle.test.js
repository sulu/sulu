/* eslint-disable flowtype/require-valid-file-annotation */
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import ModifiableRectangle from '../ModifiableRectangle';

function dispatchMouseDown(element, pageX, pageY) {
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
    const {asFragment} = render(<ModifiableRectangle height={100} width={200} />);

    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    const {asFragment} = render(<ModifiableRectangle height={100} minSizeReached={true} width={200} />);

    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with correct positions', () => {
    const {asFragment} = render(<ModifiableRectangle height={100} left={10} top={20} width={200} />);

    expect(asFragment()).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();

    render(<ModifiableRectangle height={100} onDoubleClick={clickSpy} width={200} />);

    fireEvent.doubleClick(screen.getByRole('button'));

    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    render(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);

    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    dispatchMouseDown(screen.getByRole('button'), 10, 20);
    windowListeners.mousemove({pageX: 15, pageY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 10, left: 5, width: 0, height: 0});

    windowListeners.mouseup();
    windowListeners.mousemove({pageX: 100, pageY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    render(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);

    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    dispatchMouseDown(screen.getByRole('slider'), 10, 20);
    windowListeners.mousemove({pageX: 15, pageY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, width: 5, height: 10});

    windowListeners.mouseup();
    windowListeners.mousemove({pageX: 100, pageY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
