// @flow
import type {DataNormalizer, SelectionData} from '../types';

export default class RoundingNormalizer implements DataNormalizer {
    normalize(data: SelectionData): SelectionData {
        return {
            width: Math.floor(data.width),
            height: Math.floor(data.height),
            top: Math.floor(data.top),
            left: Math.floor(data.left),
        };
    }
}
