/* eslint-disable flowtype/require-valid-file-annotation */
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import React from 'react';
import Popover from '../Popover';
import PopoverPositioner from '../PopoverPositioner';

PopoverPositioner.getCroppedDimensions = function() {
    return {
        top: 1,
        left: 2,
        height: 30,
        scrollTop: 4,
    };
};

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

afterEach(() => {
    if (Mousetrap.reset) {
        Mousetrap.reset();
    }
});

function renderPopover(props = {}) {
    return render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            open={true}
            {...props}
        >
            {(setPopoverRef, styles) => (
                <div ref={setPopoverRef} style={styles}>
                    <div>My item 1</div>
                    <div>My item 2</div>
                    <div>My item 3</div>
                </div>
            )}
        </Popover>
    );
}

function getPopoverContainer() {
    return document.querySelector('.container');
}

test('The popover should render in body when open', () => {
    renderPopover();
    const popoverContainer = getPopoverContainer();

    if (!popoverContainer) {
        throw new Error('Expected popover container');
    }

    const popoverChild = popoverContainer.firstElementChild;

    if (!popoverChild) {
        throw new Error('Expected popover child');
    }

    expect(popoverChild).toHaveStyle({
        left: '2px',
        maxHeight: '30px',
        pointerEvents: 'auto',
        position: 'fixed',
        top: '1px',
    });
    expect(document.body).toMatchSnapshot();
});

test('The popover should not render in body when not open', () => {
    renderPopover({open: false});

    expect(getPopoverContainer()).toBeNull();
});

test('The popover should request to be closed when the backdrop is clicked', async() => {
    const user = userEvent.setup();
    const onCloseSpy = jest.fn();
    renderPopover({onClose: onCloseSpy});

    await user.click(screen.getByTestId('backdrop'));
    expect(onCloseSpy).toBeCalled();
});

test('The popover should not request to be closed if it is already closed', () => {
    const onCloseSpy = jest.fn();
    renderPopover({onClose: onCloseSpy, open: false});

    fireEvent(window, new Event('blur'));
    expect(onCloseSpy).not.toBeCalled();
});

test('The popover should request to be closed when the window is blurred', () => {
    const onCloseSpy = jest.fn();
    renderPopover({onClose: onCloseSpy, open: true});

    fireEvent(window, new Event('blur'));
    expect(onCloseSpy).toBeCalled();
});

test('The popover should request to be closed when the esc key is pressed', () => {
    const closeSpy = jest.fn();
    renderPopover({onClose: closeSpy, open: true});

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('The popover should bind and unbind the esc key when overlay is opened and closed', () => {
    const closeSpy = jest.fn();
    const {rerender} = renderPopover({onClose: closeSpy, open: true});

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
    closeSpy.mockReset();

    rerender(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={false}
        >
            {(setPopoverRef, styles) => (
                <div ref={setPopoverRef} style={styles}>
                    <div>My item 1</div>
                </div>
            )}
        </Popover>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
    closeSpy.mockReset();

    rerender(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={true}
        >
            {(setPopoverRef, styles) => (
                <div ref={setPopoverRef} style={styles}>
                    <div>My item 1</div>
                </div>
            )}
        </Popover>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('The popover should pass its child ref to the parent', () => {
    const popoverChildRefSpy = jest.fn();
    render(
        <Popover anchorElement={getMockedAnchorEl()} open={true} popoverChildRef={popoverChildRefSpy}>
            {(setPopoverRef, styles) => (
                <div ref={setPopoverRef} style={styles}>
                    <div>My item 1</div>
                </div>
            )}
        </Popover>
    );

    expect(popoverChildRefSpy.mock.calls[0][0].innerHTML).toEqual('<div>My item 1</div>');
});
