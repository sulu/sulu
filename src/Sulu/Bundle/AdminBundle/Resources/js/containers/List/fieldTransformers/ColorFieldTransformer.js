// @flow
import React from 'react';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';
import colorFieldTransformerStyles from './colorFieldTransformer.scss';

export default class ColorFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!/^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test(value)) {
            log.error(`Transformer parameter "${value}" needs to be of type hexadecimal color.`);

            return null;
        }

        const style = {};
        style.backgroundColor = value;

        return <div className={colorFieldTransformerStyles.colorBox} style={style}></div>;
    }
}
