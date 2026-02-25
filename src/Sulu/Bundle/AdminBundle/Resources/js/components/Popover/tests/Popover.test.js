/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import Popover from '../Popover';
import PopoverPositioner from '../PopoverPositioner';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

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

function renderPopoverContent(setPopoverRef, styles) {
    return (
        <div data-testid="popover-content" ref={setPopoverRef} style={styles}>
            <div>My item 1</div>
            <div>My item 2</div>
            <div>My item 3</div>
        </div>
    );
}

test('The popover should render in body when open', () => {
    const {baseElement} = render(
        <Popover anchorElement={getMockedAnchorEl()} open={true}>
            {renderPopoverContent}
        </Popover>
    );

    expect(baseElement).toMatchSnapshot();
});

test('The popover should not render in body when not open', () => {
    render(
        <Popover anchorElement={getMockedAnchorEl()} open={false}>
            {renderPopoverContent}
        </Popover>
    );

    expect(screen.queryByTestId('popover-content')).not.toBeInTheDocument();
});

test('The popover should request to be closed when the backdrop is clicked', async() => {
    const onCloseSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={true}
        >
            {renderPopoverContent}
        </Popover>
    );

    await user.click(screen.getByTestId('backdrop'));
    expect(onCloseSpy).toHaveBeenCalled();
});

test('The popover should not request to be closed if it is already closed', () => {
    const onCloseSpy = jest.fn();
    render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={false}
        >
            {renderPopoverContent}
        </Popover>
    );

    window.dispatchEvent(new Event('blur'));
    expect(onCloseSpy).not.toHaveBeenCalled();
});

test('The popover should request to be closed when the window is blurred', () => {
    const onCloseSpy = jest.fn();
    render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={onCloseSpy}
            open={true}
        >
            {renderPopoverContent}
        </Popover>
    );

    window.dispatchEvent(new Event('blur'));
    expect(onCloseSpy).toHaveBeenCalled();
});

test('The popover should request to be closed when the esc key is pressed', () => {
    const closeSpy = jest.fn();
    render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={true}
        >
            {renderPopoverContent}
        </Popover>
    );

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('The popover should bind and unbind the esc key when overlay is opened and closed', () => {
    const closeSpy = jest.fn();
    const {rerender} = render(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={true}
        >
            {renderPopoverContent}
        </Popover>
    );

    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
    closeSpy.mockReset();

    rerender(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={false}
        >
            {renderPopoverContent}
        </Popover>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).not.toHaveBeenCalled();

    rerender(
        <Popover
            anchorElement={getMockedAnchorEl()}
            onClose={closeSpy}
            open={true}
        >
            {renderPopoverContent}
        </Popover>
    );
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
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

    expect(getLatestMockProps(popoverChildRefSpy).innerHTML).toEqual('<div>My item 1</div>');
});
