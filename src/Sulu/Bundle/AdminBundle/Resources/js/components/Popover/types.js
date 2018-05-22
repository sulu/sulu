// @flow

export type PopoverDimensions = {
    height: number,
    left: number,
    scrollTop: number,
    top: number,
}

export type PopoverStyle = {
    left: string,
    maxHeight: ?string,
    top: string,
};

export type VerticalCrop = {
    dimensions: PopoverDimensions,
    touchesBottomBorder: boolean,
    touchesTopBorder: boolean,
};
