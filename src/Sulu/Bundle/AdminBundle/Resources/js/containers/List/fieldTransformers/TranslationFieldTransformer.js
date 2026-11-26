// @flow
import log from 'loglevel';
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

        if (prefix) {
            log.warn(
                'The "prefix" parameter of the "translation" field transformer is deprecated and will be removed. ' +
                'Use the "key" parameter with a "{value}" placeholder instead.'
            );
        }

        return translate(prefix + value);
    }
}
