// @flow
import React from 'react';
import moment from 'moment';
import log from 'loglevel';
import type {Node} from 'react';
import {Moment} from 'moment/moment';
import classNames from 'classnames';
import type {DateTimeSkin, FieldTransformer} from '../types';
import {translate} from '../../../utils';
import dateTimeFieldTransformerStyles from './dateTimeFieldTransformer.scss';

export default class DateTimeFieldTransformer implements FieldTransformer {
    formats = {
        'default': this.getDefaultDateTime,
        'relative': this.getRelativeDateTime.bind(this),
    };

    transform(value: *, parameters: { [string]: any }): Node {
        if (!value) {
            return null;
        }

        const momentObject = moment(value, moment.ISO_8601);

        const {
            skin,
            format = 'default',
        }: {
            format: string,
            skin: ?DateTimeSkin,
        } = parameters || {};

        if (!momentObject.isValid()) {
            log.error('Invalid date given: "' + value + '". Format needs to be in "ISO 8601"');

            return null;
        }

        if (skin && typeof skin !== 'string') {
            log.error(`Transformer parameter "skin" needs to be of type string, ${typeof skin} given.`);

            return null;
        }

        const className = classNames(
            dateTimeFieldTransformerStyles.dateTime,
            {
                [dateTimeFieldTransformerStyles[skin]]: skin !== undefined,
            }
        );

        return (
            <span className={className}>
                {this.formats[format](momentObject, this.formats)}
            </span>
        );
    }

    getRelativeDateTime(momentObject: Moment) {
        const defaultFct = () => {
            return '[' + this.getDefaultDateTime(momentObject) + ']';
        };

        return momentObject.calendar({
            sameDay: '[' + translate('sulu_admin.sameDay') + '] HH:mm:ss',
            lastDay: '[' + translate('sulu_admin.lastDay') + '] HH:mm:ss',
            nextDay: '[' + translate('sulu_admin.nextDay') + '] HH:mm:ss',
            nextWeek: defaultFct(),
            lastWeek: defaultFct(),
            sameElse: defaultFct(),
        });
    }

    getDefaultDateTime(momentObject: Moment): string {
        return momentObject.format('LLL');
    }
}
