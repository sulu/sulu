/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
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

test('The popover should render in body when open', () => {
    const popover = mount(
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
    );

    popover.instance().popoverWidth = 20;
    popover.instance().popoverHeight = 100;
    popover.update();

    expect(popover.instance().dimensions.scrollTop).toBe(4);
    expect(popover.render()).toMatchSnapshot();
});

test('The popover should not render in body when not open', () => {
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
    );

    expect(view.children()).toHaveLength(0);
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

test('The popover should not request to be closed if it\' already closed', () => {
    const onCloseSpy = jest.fn();
    const popover = shallow(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={false}
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
    expect(onCloseSpy).not.toBeCalled();
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
    );
    expect(windowListeners.blur).toBeDefined();
    windowListeners.blur();
    expect(onCloseSpy).toBeCalled();
});

test('The popover should pass its child ref to the parent', () => {
    const popoverChildRefSpy = jest.fn();
    mount(
        <Popover anchorElement={getMockedAnchorEl()} open={true} popoverChildRef={popoverChildRefSpy}>
            {
                (setPopoverRef, styles) => (
                    <div ref={setPopoverRef} style={styles}>
                        <div>My item 1</div>
                    </div>
                )
            }
        </Popover>
    );

    expect(popoverChildRefSpy.mock.calls[0][0].innerHTML).toEqual('<div>My item 1</div>');
});
