// @flow
import type {Normalizer, SelectionData} from '../types';

export default class SizeNormalizer implements Normalizer {
    containerWidth: number;
    containerHeight: number;
    minWidth: number;
    minHeight: number;

    constructor(containerWidth: number, containerHeight: number, minWidth: number = 0, minHeight: number = 0) {
        this.containerWidth = containerWidth;
        this.containerHeight = containerHeight;
        this.minWidth = minWidth;
        this.minHeight = minHeight;
    }

    normalize(data: SelectionData): SelectionData {
        let width = Math.max(this.minWidth, data.width);
        let height = Math.max(this.minHeight, data.height);
        width = Math.min(this.containerWidth, width);
        height = Math.min(this.containerHeight, height);

        return {...data, width, height};
    }
}
