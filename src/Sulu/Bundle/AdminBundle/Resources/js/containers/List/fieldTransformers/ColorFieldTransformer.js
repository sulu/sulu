// @flow
import React from 'react';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';
import colorFieldTransformerStyles from './colorFieldTransformer.scss';

export default class ColorFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        if (!/^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test(value)) {
            log.error(`Invalid color given: "${value}". Format needs to be "#RGB" or "#RRGGBB".`);

            return null;
        }

        const style = {};
        style.backgroundColor = value;

        return <div className={colorFieldTransformerStyles.colorBox} style={style} />;
    }
}
