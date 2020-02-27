// @flow
import React from 'react';
import DatePicker from '../../../components/DatePicker';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import dateTimeFieldFilterTypeStyles from './dateTimeFieldFilterType.scss';

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

    getFormNode() {
        const {value} = this;

        return (
            <div className={dateTimeFieldFilterTypeStyles.dateTimeFieldFilterType}>
                <DatePicker onChange={this.handleFromChange} value={value ? value.from : undefined} />
                <DatePicker onChange={this.handleToChange} value={value ? value.to : undefined} />
            </div>
        );
    }

    getValueNode(value: ?{from?: Date, to?: Date}) {
        if (!value) {
            return null;
        }

        const {from, to} = value;

        return Promise.resolve((from ? from.toLocaleDateString() : '') + ' - ' + (to ? to.toLocaleDateString() : ''));
    }
}

export default DateTimeFieldFilterType;
