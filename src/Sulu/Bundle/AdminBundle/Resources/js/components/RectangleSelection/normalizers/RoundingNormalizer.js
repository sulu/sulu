// @flow
import type {Normalizer, SelectionData} from '../types';

export default class RoundingNormalizer implements Normalizer {
    normalize(data: SelectionData): SelectionData {
        return {
            width: Math.round(data.width),
            height: Math.round(data.height),
            top: Math.round(data.top),
            left: Math.round(data.left),
        };
    }
}
