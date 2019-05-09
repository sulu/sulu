// @flow
export interface Normalizer {
    normalize(data: SelectionData): SelectionData,
}

export type SelectionData = {
    height: number,
    left: number,
    top: number,
    width: number,
};

export type RectangleChange = {
    height: number,
    left: number,
    top: number,
    width: number,
};
