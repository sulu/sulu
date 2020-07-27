// @flow
import type {Normalizer, SelectionData} from '../types';

export default class SizeNormalizer implements Normalizer {
    maxRadius: number;
    minRadius: number;

    constructor(
        containerWidth: number,
        containerHeight: number,
        maxRadius: number | typeof undefined = undefined,
        minRadius: number | typeof undefined = undefined
    ) {
        this.maxRadius = this.getComputedMaxRadius(maxRadius, containerWidth, containerHeight);
        this.minRadius = this.getComputedMinRadius(minRadius, this.maxRadius);
    }

    getComputedMaxRadius(
        maxRadius: number | typeof undefined,
        containerWidth: number,
        containerHeight: number
    ): number {
        const containerDiagonal = Math.sqrt(
            containerWidth ** 2 + containerHeight ** 2
        );

        if (maxRadius === undefined) {
            return containerDiagonal;
        } else {
            return Math.min(maxRadius, containerDiagonal);
        }
    }

    getComputedMinRadius(minRadius: number | typeof undefined, computedMaxRadius: number): number {
        if (minRadius === undefined) {
            return 0;
        }

        return Math.min(minRadius, computedMaxRadius);
    }

    normalize(data: SelectionData): SelectionData {
        let {radius = 0} = data;

        radius = Math.min(this.maxRadius, radius);
        radius = Math.max(this.minRadius, radius);

        return {...data, radius};
    }
}
