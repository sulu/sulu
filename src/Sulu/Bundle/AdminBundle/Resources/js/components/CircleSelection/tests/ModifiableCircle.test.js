// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import ModifiableCircle from '../ModifiableCircle';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render', () => {
    const view = render(<ModifiableCircle label="" left={10} radius={100} top={20} />);

    expect(view).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();
    const circle = shallow(<ModifiableCircle label="" onDoubleClick={clickSpy} radius={100} />);

    circle.find('.circle').simulate('dblclick');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    const circle = mount(<ModifiableCircle label="" onChange={changeSpy} radius={100} />);
    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    circle.simulate('mousedown', {});
    windowListeners.mousemove({movementX: -15, movementY: -30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: -30, left: -15, radius: 100});

    windowListeners.mouseup();
    windowListeners.mousemove({movementX: 100, movementY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    const circle = mount(<ModifiableCircle label="" onChange={changeSpy} radius={100} />);
    circle.instance().circleRef = {
        getBoundingClientRect: () => ({
            left: 200,
            width: 200,
            top: 200,
            height: 200,
        }),
    };

    const resizeHandle = circle.find('.resizeHandle').first();
    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    resizeHandle.simulate('mousedown', {});
    windowListeners.mousemove({clientX: 400, clientY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, radius: 141.4213562373095});

    windowListeners.mouseup();
    windowListeners.mousemove({clientX: -10, clientY: 10});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
