// @flow
import React from 'react';
import {translate} from '../../utils/Translator';
import DatePicker from '../DatePicker';
import MultiSelect from '../MultiSelect';
import Form from '../Form';
import type {Weekday, WeeklyScheduleEntry} from './types';

type Props = {|
    index: number,
    onChange: (index: number, value: WeeklyScheduleEntry) => void,
    value: WeeklyScheduleEntry,
|};

export default class WeeklySchedule extends React.Component<Props> {
    handleDaysChange = (days: Array<Weekday>) => {
        const {index, onChange, value} = this.props;
        onChange(index, {...value, days});
    };

    handleStartChange = (start: ?Date) => {
        const {index, onChange, value} = this.props;
        onChange(index, {...value, start});
    };

    handleEndChange = (end: ?Date) => {
        const {index, onChange, value} = this.props;
        onChange(index, {...value, end});
    };

    render() {
        const {value} = this.props;

        return (
            <Form>
                <Form.Field colSpan={6} label={translate('sulu_admin.weekdays')} spaceAfter={6}>
                    <MultiSelect onChange={this.handleDaysChange} values={value.days || []}>
                        <MultiSelect.Option value="monday">{translate('sulu_admin.monday')}</MultiSelect.Option>
                        <MultiSelect.Option value="tuesday">{translate('sulu_admin.tuesday')}</MultiSelect.Option>
                        <MultiSelect.Option value="wednesday">{translate('sulu_admin.wednesday')}</MultiSelect.Option>
                        <MultiSelect.Option value="thursday">{translate('sulu_admin.thursday')}</MultiSelect.Option>
                        <MultiSelect.Option value="friday">{translate('sulu_admin.friday')}</MultiSelect.Option>
                        <MultiSelect.Option value="saturday">{translate('sulu_admin.saturday')}</MultiSelect.Option>
                        <MultiSelect.Option value="sunday">{translate('sulu_admin.sunday')}</MultiSelect.Option>
                    </MultiSelect>
                </Form.Field>
                <Form.Field colSpan={6} label={translate('sulu_admin.start')}>
                    <DatePicker
                        onChange={this.handleStartChange}
                        options={{dateFormat: false, timeFormat: true}}
                        value={value.start}
                    />
                </Form.Field>
                <Form.Field colSpan={6} label={translate('sulu_admin.end')}>
                    <DatePicker
                        onChange={this.handleEndChange}
                        options={{dateFormat: false, timeFormat: true}}
                        value={value.end}
                    />
                </Form.Field>
            </Form>
        );
    }
}
