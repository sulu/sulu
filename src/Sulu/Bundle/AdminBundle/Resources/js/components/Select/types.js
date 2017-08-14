// @flow
export type OverlayListDimensions = {
    top: number,
    left: number,
    height: number,
    scrollTop: number,
}

export type OverlayListStyle = {
    top: string,
    left: string,
    height: string,
}

export type VerticalCrop = {
    dimensions: OverlayListDimensions,
    touchesTopBorder: boolean,
    touchesBottomBorder: boolean,
};
