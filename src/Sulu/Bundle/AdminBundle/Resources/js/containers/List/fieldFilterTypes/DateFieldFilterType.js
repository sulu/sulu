// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import DatePicker from '../../../components/DatePicker';
import {translate} from '../../../utils/Translator';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import dateFieldFilterTypeStyles from './dateFieldFilterType.scss';

function formatDate(date: ?Date) {
    if (!date) {
        return '';
    }

    return date.toLocaleDateString(undefined, {year: 'numeric', month: '2-digit', day: '2-digit'});
}

function formatDateTime(date: ?Date) {
    if (!date) {
        return '';
    }

    return date.toLocaleString(
        undefined,
        {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'}
    );
}

class DateFieldFilterType extends AbstractFieldFilterType<?{from?: Date, to?: Date}> {
    handleChange = (field: string, fieldValue: ?Date) => {
        const {onChange, value} = this;

        onChange({...value, [field]: fieldValue});
    };

    handleFromChange = (value: ?Date) => {
        this.handleChange('from', value);
    };

    handleToChange = (value: ?Date) => {
        this.handleChange('to', value);
    };

    setFromInputRef(ref: ?ElementRef<'input'>) {
        if (ref) {
            ref.focus();
        }
    }

    getFormNode() {
        const {value} = this;

        return (
            <Fragment>
                <label className={dateFieldFilterTypeStyles.label}>{translate('sulu_admin.from')}</label>
                <DatePicker
                    className={dateFieldFilterTypeStyles.date}
                    inputRef={this.setFromInputRef}
                    onChange={this.handleFromChange}
                    options={{dateFormat: true, timeFormat: this.options.timeFormat}}
                    value={value ? value.from : undefined}
                />
                <label className={dateFieldFilterTypeStyles.label}>{translate('sulu_admin.until')}</label>
                <DatePicker
                    className={dateFieldFilterTypeStyles.date}
                    onChange={this.handleToChange}
                    options={{dateFormat: true, timeFormat: this.options.timeFormat}}
                    value={value ? value.to : undefined}
                />
            </Fragment>
        );
    }

    getValueNode(value: ?{from?: Date, to?: Date}) {
        if (!value) {
            return Promise.resolve(null);
        }

        const {from, to} = value;
        const dateFormatter = this.options.timeFormat ? formatDateTime : formatDate;

        if (!from && !to) {
            return Promise.resolve(null);
        }

        if (from && !to) {
            return Promise.resolve(translate('sulu_admin.from') + ' ' + dateFormatter(from));
        }

        if (!from && to) {
            return Promise.resolve(translate('sulu_admin.until') + ' ' + dateFormatter(to));
        }

        return Promise.resolve(dateFormatter(from) + ' - ' + dateFormatter(to));
    }
}

export default DateFieldFilterType;
