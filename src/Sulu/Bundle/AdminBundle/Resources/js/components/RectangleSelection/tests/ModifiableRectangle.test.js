// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ModifiableRectangle from '../ModifiableRectangle';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const createProps = (props = {}) => ({
    backdropSize: 0,
    disabled: false,
    height: 100,
    label: undefined,
    left: 0,
    minSizeReached: false,
    top: 0,
    width: 200,
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
    const {asFragment} = render(<ModifiableRectangle {...createProps()} />);
    const rectangleNode = screen.getByRole('button');

    expect(rectangleNode).toHaveStyle({
        left: '0px',
        top: '0px',
        width: '200px',
        height: '100px',
    });
    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    render(<ModifiableRectangle {...createProps({minSizeReached: true})} />);

    expect(screen.getByText('sulu_media.min_size_notification')).toBeInTheDocument();
});

test('The component should render with correct positions', () => {
    render(<ModifiableRectangle {...createProps({left: 10, top: 20})} />);
    const rectangleNode = screen.getByRole('button');

    expect(rectangleNode).toHaveStyle({
        left: '10px',
        top: '20px',
        width: '200px',
        height: '100px',
    });
});

test('The component should call the double click callback', async() => {
    const clickSpy = jest.fn();
    render(<ModifiableRectangle {...createProps({onDoubleClick: clickSpy})} />);
    const rectangleNode = screen.getByRole('button');

    await userEvent.dblClick(rectangleNode);
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const changeSpy = jest.fn();
    render(<ModifiableRectangle {...createProps({onChange: changeSpy})} />);
    const rectangleNode = screen.getByRole('button');

    dispatchMouseEvent(rectangleNode, 'mousedown', {clientX: 10, clientY: 20});
    dispatchMouseEvent(window, 'mousemove', {clientX: 15, clientY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 10, left: 5, width: 0, height: 0});

    dispatchMouseEvent(window, 'mouseup');
    dispatchMouseEvent(window, 'mousemove', {clientX: 100, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const changeSpy = jest.fn();
    render(<ModifiableRectangle {...createProps({onChange: changeSpy})} />);
    const resizeHandleNode = screen.getByRole('slider');

    dispatchMouseEvent(resizeHandleNode, 'mousedown', {clientX: 10, clientY: 20});
    dispatchMouseEvent(window, 'mousemove', {clientX: 15, clientY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, width: 5, height: 10});

    dispatchMouseEvent(window, 'mouseup');
    dispatchMouseEvent(window, 'mousemove', {clientX: 100, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
