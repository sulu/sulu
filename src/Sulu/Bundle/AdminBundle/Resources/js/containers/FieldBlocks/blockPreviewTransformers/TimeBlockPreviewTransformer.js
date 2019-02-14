// @flow
import React from 'react';
import type {Node} from 'react';
import moment from 'moment';
import log from 'loglevel';
import type {BlockPreviewTransformer} from '../types';

const format = 'HH:mm:ss';

export default class TimeBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (typeof value !== 'string') {
            return null;
        }
        const momentObject = moment(value, format);

        if (!momentObject.isValid()) {
            log.error('Invalid time given: "' + value + '". Format needs to be "' + format + '"');

            return null;
        }

        return <p>{momentObject.format('LT')}</p>;
    }
}
