// @flow
import type {FieldTransformer} from '../types';

export default class StringFieldTransformer implements FieldTransformer {
    transform(value: *): * {
        return value;
    }
}
