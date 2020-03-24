// @flow
import React from 'react';
import type {ElementRef} from 'react';
import DatePicker from '../../../components/DatePicker';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import dateTimeFieldFilterTypeStyles from './dateTimeFieldFilterType.scss';

function formatDate(date: ?Date) {
    if (!date) {
        return '';
    }

    return date.toLocaleString(undefined, {year: 'numeric', month: '2-digit', day: '2-digit'});
}

class DateTimeFieldFilterType extends AbstractFieldFilterType<?{from?: Date, to?: Date}> {
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

    setInputRef = (ref: ?ElementRef<'input'>) => {
        if (ref) {
            ref.focus();
        }
    };

    getFormNode() {
        const {value} = this;

        return (
            <div className={dateTimeFieldFilterTypeStyles.dateTimeFieldFilterType}>
                <DatePicker
                    inputRef={this.setInputRef}
                    onChange={this.handleFromChange}
                    value={value ? value.from : undefined}
                />
                <DatePicker onChange={this.handleToChange} value={value ? value.to : undefined} />
            </div>
        );
    }

    getValueNode(value: ?{from?: Date, to?: Date}) {
        if (!value) {
            return null;
        }

        const {from, to} = value;

        return Promise.resolve(formatDate(from) + ' - ' + formatDate(to));
    }
}

export default DateTimeFieldFilterType;
