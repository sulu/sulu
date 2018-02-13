/* eslint-disable flowtype/require-valid-file-annotation */

import PopoverPositioner from '../PopoverPositioner';

const HORIZONTAL_OFFSET = -20;
const VERTICAL_OFFSET = 2;

window.innerWidth = 1920;
window.innerHeight = 1000;

const getCroppedDimensionsArguments = (croppedDimensionsArguments = {}) => {
    const mergedCroppedDimensionsArguments = Object.assign({}, {
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
    }, croppedDimensionsArguments);

    return [
        mergedCroppedDimensionsArguments.popoverWidth,
        mergedCroppedDimensionsArguments.popoverHeight,
        mergedCroppedDimensionsArguments.anchorTop,
        mergedCroppedDimensionsArguments.anchorLeft,
        mergedCroppedDimensionsArguments.anchorWidth,
        mergedCroppedDimensionsArguments.anchorHeight,
        mergedCroppedDimensionsArguments.horizontalOffset,
        mergedCroppedDimensionsArguments.verticalOffset,
        mergedCroppedDimensionsArguments.centerChildOffsetTop,
        mergedCroppedDimensionsArguments.alignOnVerticalEdges,
        mergedCroppedDimensionsArguments.windowWidth,
        mergedCroppedDimensionsArguments.windowHeight,
    ];
};

test('The positioner should return the correct dimensions when the popover fits into the screen', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments())
    )).toEqual({top: 302, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover needs to be cropped at the top', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments({
            anchorTop: 50,
            centerChildOffsetTop: 300,
        }))
    )).toEqual({top: 10, left: 480, height: 242, scrollTop: 258});
});

test('The positioner should return the correct dimensions when the popover undercuts the min height at the top', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments({
            anchorTop: 50,
            centerChildOffsetTop: 450,
        }))
    )).toEqual({top: 52, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover needs to be cropped at the bottom', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments({
            anchorTop: 920,
            centerChildOffsetTop: 300,
        }))
    )).toEqual({top: 622, left: 480, height: 368, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover undercuts the min height at the bottom',
    () => {
        expect(PopoverPositioner.getCroppedDimensions(
            ...(getCroppedDimensionsArguments({
                anchorTop: 920,
                centerChildOffsetTop: 10,
            }))
        )).toEqual({top: 448, left: 480, height: 500, scrollTop: 0});
    }
);

test('The positioner should return the correct dimensions when the popover overflows to the left', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments({
            popoverWidth: 600,
            anchorTop: 400,
            anchorLeft: 10,
            centerChildOffsetTop: 100,
        }))
    )).toEqual({top: 302, left: 10, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover overflows to the right', () => {
    expect(PopoverPositioner.getCroppedDimensions(
        ...(getCroppedDimensionsArguments({
            popoverWidth: 600,
            anchorTop: 400,
            anchorLeft: 2000,
            centerChildOffsetTop: 100,
        }))
    )).toEqual({top: 302, left: 1310, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the popover undercuts the min height at the bottom 2',
    () => {
        window.innerWidth = 800;
        window.innerHeight = 800;

        expect(PopoverPositioner.getCroppedDimensions(
            ...(getCroppedDimensionsArguments({
                popoverWidth: 200,
                popoverHeight: 400,
                anchorTop: 550,
                anchorLeft: 250,
                anchorWidth: 150,
                anchorHeight: 30,
                alignOnVerticalEdges: true,
            }))
        )).toEqual({top: 148, left: 230, height: 400, scrollTop: 0});
    }
);
