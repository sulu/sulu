// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ModifiableCircle from '../ModifiableCircle';

jest.mock('mobx-react', () => ({
    observer: (Component) => Component,
}));

const createProps = (props = {}) => ({
    disabled: false,
    label: undefined,
    left: 0,
    radius: 0,
    resizable: true,
    skin: 'outlined',
    top: 0,
    ...props,
});

const dispatchMouseEvent = (
    target: EventTarget,
    type: string,
    {clientX = 0, clientY = 0, pageX = clientX, pageY = clientY}: Object = {}
) => {
    const event = new MouseEvent(type, {bubbles: true, cancelable: true, clientX, clientY});
    Object.defineProperty(event, 'pageX', {value: pageX});
    Object.defineProperty(event, 'pageY', {value: pageY});
    target.dispatchEvent(event);
};

test('The component should render', () => {
    const {asFragment} = render(<ModifiableCircle {...createProps({label: '', left: 10, radius: 100, top: 20})} />);
    const circleNode = screen.getByRole('button');

    expect(circleNode).toHaveStyle({
        left: '10px',
        top: '20px',
        width: '200px',
        height: '200px',
    });
    expect(asFragment()).toMatchSnapshot();
});

test('The component should call the double click callback', async() => {
    const clickSpy = jest.fn();
    render(<ModifiableCircle {...createProps({label: '', onDoubleClick: clickSpy, radius: 100})} />);
    const circleNode = screen.getByRole('button');

    await userEvent.dblClick(circleNode);
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const changeSpy = jest.fn();
    render(<ModifiableCircle {...createProps({label: '', onChange: changeSpy, radius: 100})} />);
    const circleNode = screen.getByRole('button');

    dispatchMouseEvent(circleNode, 'mousedown', {clientX: 10, clientY: 20});
    dispatchMouseEvent(window, 'mousemove', {clientX: 15, clientY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 10, left: 5, radius: 0});

    dispatchMouseEvent(window, 'mouseup');
    dispatchMouseEvent(window, 'mousemove', {clientX: 100, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const changeSpy = jest.fn();
    render(<ModifiableCircle {...createProps({label: '', onChange: changeSpy, radius: 100})} />);
    const circleNode: any = screen.getByRole('button');
    const resizeHandleNode = screen.getByRole('slider');

    circleNode.getBoundingClientRect = () => ({
        bottom: 400,
        height: 200,
        left: 200,
        right: 400,
        toJSON: jest.fn(),
        top: 200,
        width: 200,
        x: 200,
        y: 200,
    }: any);

    dispatchMouseEvent(resizeHandleNode, 'mousedown', {clientX: 10, clientY: 20});
    dispatchMouseEvent(window, 'mousemove', {clientX: 400, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, radius: 41.42135623730951});

    dispatchMouseEvent(window, 'mouseup');
    dispatchMouseEvent(window, 'mousemove', {clientX: -10, clientY: 10});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
