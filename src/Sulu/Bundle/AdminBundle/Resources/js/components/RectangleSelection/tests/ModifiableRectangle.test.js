/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import ModifiableRectangle from '../ModifiableRectangle';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render', () => {
    const view = render(<ModifiableRectangle height={100} width={200} />);

    expect(view).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    const view = render(<ModifiableRectangle height={100} minSizeReached={true} width={200} />);

    expect(view).toMatchSnapshot();
});

test('The component should render with correct positions', () => {
    const view = render(<ModifiableRectangle height={100} left={10} top={20} width={200} />);

    expect(view).toMatchSnapshot();
});

test('The component should call the double click callback', () => {
    const clickSpy = jest.fn();
    const rectangle = shallow(<ModifiableRectangle height={100} onDoubleClick={clickSpy} width={200} />);

    rectangle.find('.rectangle').simulate('dblclick');
    expect(clickSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on move', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    const rectangle = mount(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);
    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    rectangle.simulate('mousedown', {});
    windowListeners.mousemove({movementX: -15, movementY: -30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: -30, left: -15, width: 0, height: 0});

    windowListeners.mouseup();
    windowListeners.mousemove({movementX: 100, movementY: 200});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('The component should call the change callback on resize', () => {
    const windowListeners = {};
    const changeSpy = jest.fn();
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);

    const rectangle = mount(<ModifiableRectangle height={100} onChange={changeSpy} width={200} />);
    const resizeHandle = rectangle.find('.resizeHandle');
    expect(windowListeners.mousemove).toBeDefined();
    expect(windowListeners.mouseup).toBeDefined();

    resizeHandle.simulate('mousedown', {});
    windowListeners.mousemove({movementX: 15, movementY: 30});

    expect(changeSpy).toHaveBeenCalledTimes(1);
    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, width: 15, height: 30});

    windowListeners.mouseup();
    windowListeners.mousemove({movementX: -10, movementY: 10});

    expect(changeSpy).toHaveBeenCalledTimes(1);
});
