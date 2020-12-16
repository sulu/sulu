// @flow
import React from 'react';
import SchedulesComponent from '../../../components/Schedules';
import type {ScheduleEntry} from '../../../components/Schedules';
import type {FieldTypeProps} from '../../../types';

type Props = FieldTypeProps<Array<ScheduleEntry>>;

export default class Schedules extends React.Component<Props> {
    handleChange = (value: Array<ScheduleEntry>) => {
        const {onChange, onFinish} = this.props;
        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <SchedulesComponent disabled={!!disabled} onChange={this.handleChange} value={value} />
        );
    }
}
