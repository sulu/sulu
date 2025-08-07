// @flow
import React from 'react';
import moment from 'moment';
import log from 'loglevel';
import classNames from 'classnames';
import {translate} from '../../../utils';
import dateTimeFieldTransformerStyles from './dateTimeFieldTransformer.scss';
import type {FieldTransformer} from '../types';
import type {Node} from 'react';

export type Skin = 'default' | 'light';

export default class DateTimeFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: { [string]: any }): Node {
        if (!value) {
            return null;
        }

        let momentObject;

        if (typeof value === 'number' || (typeof value === 'string' && /^\d+$/.test(value))) {
            const timestamp = Number(value);
            // If timestamp is in seconds (less than 10 digits), convert to milliseconds
            const timestampMs = timestamp < 10000000000 ? timestamp * 1000 : timestamp;
            momentObject = moment(timestampMs);
        } else {
            momentObject = moment(value, moment.ISO_8601);
        }

        if (!momentObject.isValid()) {
            log.error('Invalid date given: "' + value + '". Format needs to be in "ISO 8601" or a valid timestamp.');

            return null;
        }

        const {
            skin = 'default',
            format = 'default',
        }: {
            format: string,
            skin: Skin,
        } = parameters || {};

        if (typeof skin !== 'string') {
            log.error(`Transformer parameter "skin" needs to be of type string, ${typeof skin} given.`);

            return null;
        }

        let formattedDate;
        switch (format){
            case 'relative':
                formattedDate = this.getRelativeDateTime(momentObject);
                break;
            default:
                formattedDate = this.getDefaultDateTime(momentObject);
                break;
        }

        const className = classNames(
            dateTimeFieldTransformerStyles[skin]
        );
        return (
            <span className={className}>
                {formattedDate}
            </span>
        );
    }

    getRelativeDateTime(momentObject: moment) {
        const defaultFct = () => {
            return '[' + this.getDefaultDateTime(momentObject) + ']';
        };

        return momentObject.calendar({
            sameDay: '[' + translate('sulu_admin.sameDay') + '] HH:mm',
            lastDay: '[' + translate('sulu_admin.lastDay') + '] HH:mm',
            nextDay: '[' + translate('sulu_admin.nextDay') + '] HH:mm',
            nextWeek: defaultFct(),
            lastWeek: defaultFct(),
            sameElse: defaultFct(),
        });
    }

    getDefaultDateTime(momentObject: moment): string {
        return momentObject.format('LLL');
    }
}
