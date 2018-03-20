// @flow
import React from 'react';
import moment from 'moment';
import DatePickerComponent from '../../../components/DatePicker';
import type {FieldTypeProps} from '../../../types';

export default class DatePicker extends React.Component<FieldTypeProps<?string>> {
    handleChange = (value: ?Date) => {
        const {onChange, onFinish} = this.props;
        const stringValue = value ? moment(value).format('YYYY-MM-DD') : undefined;

        onChange(stringValue);

        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {value, error} = this.props;
        const date = value ? moment(value, 'YYYY-MM-DD').toDate() : undefined;

        return (
            <DatePickerComponent onChange={this.handleChange} valid={!error} value={date} />
        );
    }
}
