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
        let left = Math.max(0, data.left);
        let top = Math.max(0, data.top);
        left = Math.min(left, this.containerWidth - data.width);
        top = Math.min(top, this.containerHeight - data.height);

        return {...data, left, top};
    }
}
