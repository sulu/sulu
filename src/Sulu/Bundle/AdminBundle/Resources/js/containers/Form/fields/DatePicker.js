// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import moment from 'moment';
import DatePickerComponent from '../../../components/DatePicker';
import type {FieldTypeProps} from '../../../types';

function createStringValue(value: ?Date, format: string) {
    if (!value) {
        return undefined;
    }

    return moment(value).format(format);
}

function getValue(value: ?string, format: string): ?moment {
    if (!value) {
        return undefined;
    }

    const momentObject = moment(value, format);

    if (!momentObject.isValid()) {
        return undefined;
    }

    return momentObject.toDate();
}

@observer
class DatePicker extends React.Component<FieldTypeProps<?string>> {
    @computed get format() {
        const {fieldTypeOptions} = this.props;
        const {dateFormat, timeFormat} = fieldTypeOptions;

        if (dateFormat && timeFormat) {
            return 'YYYY-MM-DDTHH:mm:ss';
        }

        if (dateFormat) {
            return 'YYYY-MM-DD';
        }

        return 'HH:mm:ss';
    }

    handleChange = (value: ?Date) => {
        const {onChange, onFinish} = this.props;
        const stringValue = createStringValue(value, this.format);

        onChange(stringValue);
        onFinish();
    };

    render() {
        const {dataPath, disabled, error, fieldTypeOptions, value} = this.props;
        const {dateFormat, timeFormat} = fieldTypeOptions;

        if (dateFormat === undefined || timeFormat === undefined) {
            throw new Error('The "dateFormat" and "timeFormat" fieldTypeOption have to be set!');
        }

        const options = {};

        if (timeFormat) {
            options.timeFormat = timeFormat;
        }

        if (!dateFormat) {
            options.dateFormat = false;
        }

        return (
            <DatePickerComponent
                disabled={!!disabled}
                id={dataPath}
                onChange={this.handleChange}
                options={options}
                valid={!error}
                value={getValue(value, this.format)}
            />
        );
    }
}

export default DatePicker;
