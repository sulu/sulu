// @flow
import type {Normalizer, SelectionData} from '../types';

export default class RoundingNormalizer implements Normalizer {
    normalize(data: SelectionData): SelectionData {
        const {left, top, radius = 0} = data;

        return {
            left: Math.round(left),
            top: Math.round(top),
            radius: Math.round(radius),
        };
    }
}
