// @flow

export type PopoverDimensions = {
    top: number,
    left: number,
    height: number,
    scrollTop: number,
}

export type PopoverStyle = {
    top: string,
    left: string,
    maxHeight: ?string,
};

export type VerticalCrop = {
    dimensions: PopoverDimensions,
    touchesTopBorder: boolean,
    touchesBottomBorder: boolean,
};
