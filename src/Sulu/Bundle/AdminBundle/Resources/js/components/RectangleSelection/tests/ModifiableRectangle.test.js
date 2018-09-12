/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import ModifiableRectangle from '../ModifiableRectangle';

test('The component should render', () => {
    const view = render(<ModifiableRectangle height={100} width={200} />);

    expect(view).toMatchSnapshot();
});

test('The component should render with correct positions', () => {
    const view = render(<ModifiableRectangle height={100} left={10} top={20} width={200} />);

    expect(view).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();
    const rectangle = shallow(<ModifiableRectangle height={100} onDoubleClick={clickSpy} width={200} />);

    rectangle.simulate('dblclick');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const bodyListeners = {};
    const changeSpy = jest.fn();
    document.body.addEventListener = jest.fn((event, cb) => bodyListeners[event] = cb);

    const rectangle = mount(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);
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

    const rectangle = mount(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);
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
