/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import OverlayList from '../OverlayList';
import Option from '../Option';

jest.mock('../../../services/DOM/afterElementsRendered');

jest.mock('../OverlayListPositioner', () => {
    const OverlayListPositioner = require.requireActual('../OverlayListPositioner').default;

    return class extends OverlayListPositioner {
        static getCroppedDimensions() {
            return {
                top: 1,
                left: 2,
                height: 30,
                scrollTop: 4,
            };
        }
    };
});

afterEach(() => document.body.innerHTML = '');

test('The list should render in body when open', () => {
    const body = document.body;
    const view = mount(
        <OverlayList isOpen={true}>
            <Option value="option-1">My option 1</Option>
            <Option value="option-2">My option 2</Option>
            <Option value="option-3">My option 3</Option>
        </OverlayList>
    ).render();
    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The list should not render in body when not open', () => {
    const body = document.body;
    const view = mount(
        <OverlayList isOpen={false}>
            <Option value="option-1">My option 1</Option>
            <Option value="option-2">My option 2</Option>
            <Option value="option-3">My option 3</Option>
        </OverlayList>
    ).render();
    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The list should request to be closed when the backdrop is clicked', () => {
    const onCloseSpy = jest.fn();
    const list = shallow(
        <OverlayList isOpen={true} onClose={onCloseSpy}>
            <Option value="option-1">My option 1</Option>
        </OverlayList>
    );
    list.find('Backdrop').simulate('click');
    expect(onCloseSpy).toBeCalled();
});

test('The list should request to be closed when the window is blurred', () => {
    const windowListeners = {};
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);
    const onCloseSpy = jest.fn();
    mount(
        <OverlayList isOpen={true} onClose={onCloseSpy}>
            <Option value="option-1">My option 1</Option>
        </OverlayList>
    ).render();
    expect(windowListeners.blur).toBeDefined();
    windowListeners.blur();
    expect(onCloseSpy).toBeCalled();
});

test('The list should take its dimensions from the positioner', () => {
    const body = document.body;
    const list = mount(
        <OverlayList isOpen={true}>
            <Option value="option-1">My option 1</Option>
        </OverlayList>
    );
    list.instance().scrollHeight = 100;
    list.instance().scrollWidth = 20;
    list.instance().centeredChildRelativeTop = 30;
    list.update();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
    expect(list.instance().scrollTop).toBe(4);
});
