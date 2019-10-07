// @flow
import type {Normalizer, SelectionData} from '../types';

export default class RatioNormalizer implements Normalizer {
    minWidth: number;
    minHeight: number;
    containerWidth: number;
    containerHeight: number;

    constructor(containerWidth: number, containerHeight: number, width: number, height: number) {
        this.containerWidth = containerWidth;
        this.containerHeight = containerHeight;
        this.minWidth = width;
        this.minHeight = height;
    }

    normalize(data: SelectionData): SelectionData {
        let height = data.height;
        let width;
        const calculatedWidth = height * (this.minWidth / this.minHeight);

        if (calculatedWidth > this.containerWidth) {
            width = this.containerWidth;
            height = width * (this.minHeight / this.minWidth);
        } else {
            width = calculatedWidth;
        }

        return {...data, width, height};
    }
}
