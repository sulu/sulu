// @flow
import React from 'react';
import moment from 'moment';
import log from 'loglevel';
import type {Node} from 'react';
import {Moment} from 'moment/moment';
import classNames from 'classnames';
import type {FieldTransformer} from '../types';
import {translate} from '../../../utils';
import dateTimeFieldTransformerStyles from './dateTimeFieldTransformer.scss';

export type Skin = 'default' | 'light';

export default class DateTimeFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: { [string]: any }): Node {
        if (!value) {
            return null;
        }

        const momentObject = moment(value, moment.ISO_8601);

        if (!momentObject.isValid()) {
            log.error('Invalid date given: "' + value + '". Format needs to be in "ISO 8601"');

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
