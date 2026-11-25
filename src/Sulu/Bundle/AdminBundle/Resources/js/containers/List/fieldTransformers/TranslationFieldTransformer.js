// @flow
import {translate} from '../../../utils';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class TranslationFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: { [string]: any }): Node {
        if (value === undefined) {
            return null;
        }

        const {key, prefix = ''} = parameters;

        if (key) {
            return translate(key.replaceAll('{value}', String(value)), {value});
        }

        // @deprecated use the "key" parameter with a "{value}" placeholder instead
        return translate(prefix + value);
    }
}
