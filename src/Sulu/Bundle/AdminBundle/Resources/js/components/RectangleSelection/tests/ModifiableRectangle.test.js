/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import ModifiableRectangle from '../ModifiableRectangle';
import React from 'react';

test('The component should render', () => {
    const view = render(<ModifiableRectangle width={200} height={100} />);

    expect(view).toMatchSnapshot();
});

test('The component should render with correct positions', () => {
    const view = render(<ModifiableRectangle width={200} height={100} left={10} top={20} />);

    expect(view).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();
    const rectangle = shallow(<ModifiableRectangle onDoubleClick={clickSpy} width={200} height={100} />);

    rectangle.simulate('dblclick');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const bodyListeners = {};
    const changeSpy = jest.fn();
    document.body.addEventListener = jest.fn((event, cb) => bodyListeners[event] = cb);

    const rectangle = mount(<ModifiableRectangle onChange={changeSpy} width={200} height={100} />);
    expect(bodyListeners.mousemove).toBeDefined();
    expect(bodyListeners.mouseup).toBeDefined();

    rectangle.simulate('mousedown', {pageX: 10, pageY: 20});
    bodyListeners.mousemove({pageX: 15, pageY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 10, left: 5, width: 0, height: 0});

    bodyListeners.mouseup();
    bodyListeners.mousemove({pageX: 100, pageY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const bodyListeners = {};
    const changeSpy = jest.fn();
    document.body.addEventListener = jest.fn((event, cb) => bodyListeners[event] = cb);

    const rectangle = mount(<ModifiableRectangle onChange={changeSpy} width={200} height={100} />);
    const resizeHandle = rectangle.find('.resizeHandle');
    expect(bodyListeners.mousemove).toBeDefined();
    expect(bodyListeners.mouseup).toBeDefined();

    resizeHandle.simulate('mousedown', {pageX: 10, pageY: 20});
    bodyListeners.mousemove({pageX: 15, pageY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, width: 5, height: 10});

    bodyListeners.mouseup();
    bodyListeners.mousemove({pageX: 100, pageY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
