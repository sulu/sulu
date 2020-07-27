// @flow
import type {Normalizer, SelectionData} from '../types';

export default class PositionNormalizer implements Normalizer {
    containerWidth: number;
    containerHeight: number;

    constructor(containerWidth: number, containerHeight: number) {
        this.containerWidth = containerWidth;
        this.containerHeight = containerHeight;
    }

    normalize(data: SelectionData): SelectionData {
        let {left, top} = data;

        left = Math.max(0, left);
        left = Math.min(left, this.containerWidth);
        top = Math.max(0, top);
        top = Math.min(top, this.containerHeight);

        return {...data, left, top};
    }
}
