/* eslint-disable flowtype/require-valid-file-annotation */

import PopoverPositioner from '../PopoverPositioner';

const HORIZONTAL_OFFSET = -20;
const VERTICAL_OFFSET = 2;

const getCroppedDimensionsArgs = (changes = {}) => {
    const argsObj = Object.assign({}, {
        popoverWidth: 300,
        popoverHeight: 500,
        anchorTop: 400,
        anchorLeft: 500,
        anchorWidth: 500,
        anchorHeight: 30,
        horizontalOffset: HORIZONTAL_OFFSET,
        verticalOffset: VERTICAL_OFFSET,
        centerChildOffsetTop: 100,
        alignOnVerticalEdges: false,
        windowWidth: 1920,
        windowHeight: 1000,
    }, changes);

    return [
        argsObj.popoverWidth,
        argsObj.popoverHeight,
        argsObj.anchorTop,
        argsObj.anchorLeft,
        argsObj.anchorWidth,
        argsObj.anchorHeight,
        argsObj.horizontalOffset,
        argsObj.verticalOffset,
        argsObj.centerChildOffsetTop,
        argsObj.alignOnVerticalEdges,
        argsObj.windowWidth,
        argsObj.windowHeight,
    ];
};

test('The positioner should return the correct dimensions when the popover fits into the screen', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs())
    )).toEqual({top: 302, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover needs to be cropped at the top', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs({
            anchorTop: 50,
            centerChildOffsetTop: 300,
        }))
    )).toEqual({top: 10, left: 480, height: 242, scrollTop: 258});
});

test('The positioner should return the correct dimensions when the popover undercuts the min height at the top', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs({
            anchorTop: 50,
            centerChildOffsetTop: 450,
        }))
    )).toEqual({top: 52, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover needs to be cropped at the bottom', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs({
            anchorTop: 920,
            centerChildOffsetTop: 300,
        }))
    )).toEqual({top: 622, left: 480, height: 368, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover undercuts the min height at the bottom',
    () => {
        expect(PopoverPositioner.getCroppedDimensions(
            ...(getCroppedDimensionsArgs({
                anchorTop: 920,
                centerChildOffsetTop: 10,
            }))
        )).toEqual({top: 448, left: 480, height: 500, scrollTop: 0});
    }
);

test('The positioner should return the correct dimensions when the popover overflows to the left', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs({
            popoverWidth: 600,
            anchorTop: 400,
            anchorLeft: 10,
            centerChildOffsetTop: 100,
        }))
    )).toEqual({top: 302, left: 10, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover overflows to the right', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArgs({
            popoverWidth: 600,
            anchorTop: 400,
            anchorLeft: 2000,
            centerChildOffsetTop: 100,
        }))
    )).toEqual({top: 302, left: 1310, height: 500, scrollTop: 0});
});
