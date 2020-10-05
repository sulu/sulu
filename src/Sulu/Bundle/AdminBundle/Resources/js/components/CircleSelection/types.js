// @flow
export interface Normalizer {
    normalize(data: SelectionData): SelectionData,
}

export type SelectionData = {
    left: number,
    radius: number,
    top: number,
};

export type CircleChange = {
    left: number,
    radius: number,
    top: number,
};
