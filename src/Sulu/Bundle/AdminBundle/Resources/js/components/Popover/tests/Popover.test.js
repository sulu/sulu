/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import Popover from '../Popover';

jest.mock('../PopoverPositioner', () => {
    const PopoverPositioner = require.requireActual('../PopoverPositioner').default;

    return class extends PopoverPositioner {
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

const getMockedAnchorEl = () => ({
    getBoundingClientRect() {
        return {
            x: 10,
            y: 10,
            width: 10,
            height: 10,
            top: 10,
            right: 10,
            bottom: 10,
            left: 10,
        };
    },
});

afterEach(() => document.body.innerHTML = '');

test('The popover should render in body when open', () => {
    const body = document.body;
    const view = mount(
        <Popover
            anchorElement={getMockedAnchorEl()}
            open={true}
        >
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                        <div>My item 2</div>
                        <div>My item 3</div>
                    </div>
                )
            }
        </Popover>
    ).render();
    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The popover should not render in body when not open', () => {
    const body = document.body;
    const view = mount(
        <Popover
            anchorElement={getMockedAnchorEl()}
            open={false}
        >
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                        <div>My item 2</div>
                        <div>My item 3</div>
                    </div>
                )
            }
        </Popover>
    ).render();
    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The popover should request to be closed when the backdrop is clicked', () => {
    const onCloseSpy = jest.fn();
    const popover = shallow(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={true}
        >
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                    </div>
                )
            }
        </Popover>
    );
    popover.find('Backdrop').simulate('click');
    expect(onCloseSpy).toBeCalled();
});

test('The popover should request to be closed when the window is blurred', () => {
    const windowListeners = {};
    window.addEventListener = jest.fn((event, cb) => windowListeners[event] = cb);
    const onCloseSpy = jest.fn();
    mount(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={true}
        >
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                    </div>
                )
            }
        </Popover>
    ).render();
    expect(windowListeners.blur).toBeDefined();
    windowListeners.blur();
    expect(onCloseSpy).toBeCalled();
});

test('The popover should take its dimensions from the positioner', () => {
    const body = document.body;
    const popover = mount(
        <Popover anchorElement={getMockedAnchorEl()} open={true}>
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                    </div>
                )
            }
        </Popover>
    );
    popover.instance().popoverWidth = 20;
    popover.instance().popoverHeight = 100;
    popover.update();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
    expect(popover.instance().dimensions.scrollTop).toBe(4);
});
