// @flow
import React from 'react';
import moment from 'moment';
import DatePickerComponent from '../../../components/DatePicker';
import type {FieldTypeProps} from '../../../types';

const format = 'YYYY-MM-DD';

function createStringValue(value: ?Date) {
    if (!value) {
        return undefined;
    }

    return moment(value).format(format);
}

function getValue(value: ?string): ?moment {
    if (!value) {
        return undefined;
    }

    const momentObject = moment(value, format);

    if (!momentObject.isValid()) {
        return undefined;
    }

    return momentObject.toDate();
}

export default class DatePicker extends React.Component<FieldTypeProps<?string>> {
    handleChange = (value: ?Date) => {
        const {onChange, onFinish} = this.props;
        const stringValue = createStringValue(value);

        onChange(stringValue);
        onFinish();
    };

    render() {
        const {value, error} = this.props;

        return (
            <DatePickerComponent onChange={this.handleChange} valid={!error} value={getValue(value)} />
        );
    }
}
