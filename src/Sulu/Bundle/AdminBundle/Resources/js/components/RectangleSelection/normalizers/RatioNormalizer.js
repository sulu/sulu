// @flow
import type {Normalizer, SelectionData} from '../types';

export default class RatioNormalizer implements Normalizer {
    width: number;
    height: number;

    constructor(width: number, height: number) {
        this.width = width;
        this.height = height;
    }

    normalize(data: SelectionData): SelectionData {
        let height = data.height;
        let width = data.width;
        let calculatedHeight = width * (this.height / this.width);
        let calculatedWidth = height * (this.width / this.height);

        if (calculatedWidth <= width) {
            width = calculatedWidth;
        } else {
            height = calculatedHeight;
        }

        return {...data, width, height};
    }
}
