// @flow
export interface DataNormalizer {
    normalize(data: SelectionData): SelectionData,
}

export type SelectionData = {
    left: number,
    top: number,
    width: number,
    height: number,
};

export type RectangleChange = {
    top: number,
    left: number,
    width: number,
    height: number,
};
