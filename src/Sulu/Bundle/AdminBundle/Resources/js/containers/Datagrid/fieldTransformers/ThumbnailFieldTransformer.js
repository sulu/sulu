// @flow
import React from 'react';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

const IMAGE_FORMAT = 'sulu-40x40';

export default class ThumbnailFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return undefined;
        }

        if ('object' !== typeof value) {
            log.error('Invalid type given: "' + typeof value + '". "object" is needed.');

            return undefined;
        }

        if (!value.hasOwnProperty(IMAGE_FORMAT)) {
            log.error('Object needs property "' + IMAGE_FORMAT + '".');

            return undefined;
        }

        return <img src={value[IMAGE_FORMAT]} alt={value.alt} />;
    }
}
