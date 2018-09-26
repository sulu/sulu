// @flow
import type {PopoverDimensions, PopoverStyle, VerticalCrop} from './types';

const MIN_HEIGHT = 200;
const PADDING_TO_WINDOW = 10;

/**
 * The class is responsible for calculating the position of the popover on the screen when opened.
 * Generally, it positions the popover above an anchor element. Moreover the popover is shifted, such that
 * a designated element in the popover is right above the anchor element.
 * It is made sure that the popover does not overflow the borders of the screen and even keeps a padding to the borders.
 *
 * The behaviour described to this point, however, is broken at one point: When the height of the popover undercuts
 * a certain minimum height, the popover is positioned from the anchor element upwards or downwards depending on if
 * the popover overflows the bottom or top border of the screen.
 */
export default class PopoverPositioner {
    static dimensionsToStyle(dimensions: PopoverDimensions): PopoverStyle {
        const style = {
            top: dimensions.top + 'px',
            left: dimensions.left + 'px',
            maxHeight: undefined,
        };

        if (dimensions.height) {
            style.maxHeight = dimensions.height + 'px';
        }

        return style;
    }

    static getCroppedDimensions(
        popoverWidth: number,
        popoverHeight: number,
        anchorTop: number,
        anchorLeft: number,
        anchorWidth: number,
        anchorHeight: number,
        horizontalOffset: number,
        verticalOffset: number,
        centerChildOffsetTop: number,
        alignOnVerticalAnchorEdges: boolean = true
    ): PopoverDimensions {
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        // First, the popover is positioned without taking the screen borders or the minimum height into account.
        const dimensions = {
            top: anchorTop + verticalOffset - centerChildOffsetTop,
            left: anchorLeft + horizontalOffset,
            height: popoverHeight,
            scrollTop: 0,
        };

        if (alignOnVerticalAnchorEdges) {
            dimensions.top = anchorTop + verticalOffset + anchorHeight;
        } else if (anchorTop < PADDING_TO_WINDOW) {
            dimensions.top = PADDING_TO_WINDOW;
        } else if (anchorTop + anchorHeight > windowHeight - PADDING_TO_WINDOW) {
            dimensions.top = windowHeight - popoverHeight - PADDING_TO_WINDOW;
        }

        let crop = PopoverPositioner.cropVerticalDimensions(dimensions, windowHeight);

        // If after making sure, the popover does not overflow the top and the bottom border of the screen,
        // the popover succeeds the minimum height, no more steps have to be taken.
        if (!alignOnVerticalAnchorEdges && crop.dimensions.height >= MIN_HEIGHT) {
            return PopoverPositioner.cropHorizontalDimensions(crop.dimensions, windowWidth, popoverWidth, anchorLeft, anchorWidth);
        }

        // If the minimum height is undercut and the top border of the screen is touched, the popover gets
        // positioned from the anchor downwards.
        if (crop.touchesTopBorder) {
            dimensions.top = anchorTop + verticalOffset;
        }

        // If the bottom border is touched, it gets positioned from the anchor upwards.
        if (crop.touchesBottomBorder) {
            if (alignOnVerticalAnchorEdges) {
                dimensions.top = anchorTop - popoverHeight - verticalOffset;
            } else {
                dimensions.top = anchorTop + anchorHeight - popoverHeight - verticalOffset;
            }
        }

        // After moving the popover it has to be made sure one more time that the popover does not overflow the borders.
        crop = PopoverPositioner.cropVerticalDimensions(dimensions, windowHeight);

        return PopoverPositioner.cropHorizontalDimensions(crop.dimensions, windowWidth, popoverWidth, anchorLeft, anchorWidth);
    }

    static cropVerticalDimensions(dimensions: PopoverDimensions, windowHeight: number): VerticalCrop {
        const newDimensions = {...dimensions};
        let touchesTopBorder = false;
        let touchesBottomBorder = false;

        if (dimensions.top < PADDING_TO_WINDOW) {
            const newHeight = dimensions.height + dimensions.top - PADDING_TO_WINDOW;
            newDimensions.top = PADDING_TO_WINDOW;
            newDimensions.height = (newHeight < 0) ? dimensions.height : newHeight;
            newDimensions.scrollTop = -dimensions.top + PADDING_TO_WINDOW;
            touchesTopBorder = true;
        }

        if (newDimensions.top + newDimensions.height > windowHeight - PADDING_TO_WINDOW) {
            newDimensions.height = windowHeight - newDimensions.top - PADDING_TO_WINDOW;
            touchesBottomBorder = true;
        }

        return {dimensions: newDimensions, touchesTopBorder, touchesBottomBorder};
    }

    static cropHorizontalDimensions(
        dimensions: PopoverDimensions,
        windowWidth: number,
        popoverWidth: number,
        anchorLeft: number,
        anchorWidth: number
    ): PopoverDimensions {
        const newDimensions = {...dimensions};
        newDimensions.left = Math.max(PADDING_TO_WINDOW, newDimensions.left);

        if ((popoverWidth + newDimensions.left) > windowWidth) {
            // calc from right side
            newDimensions.left = anchorLeft + anchorWidth - popoverWidth;
        }

        newDimensions.left = Math.min(windowWidth - popoverWidth - PADDING_TO_WINDOW, newDimensions.left);
        return newDimensions;
    }
}
