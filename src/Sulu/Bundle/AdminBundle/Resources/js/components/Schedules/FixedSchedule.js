// @flow
import React from 'react';
import {translate} from '../../utils/Translator';
import DatePicker from '../DatePicker';
import Form from '../Form';
import type {FixedScheduleEntry} from './types';

type Props = {|
    index: number,
    onChange: (index: number, value: FixedScheduleEntry) => void,
    value: FixedScheduleEntry,
|};

export default class FixedSchedule extends React.Component<Props> {
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
                <Form.Field colSpan={6} label={translate('sulu_admin.start')}>
                    <DatePicker
                        onChange={this.handleStartChange}
                        options={{dateFormat: true, timeFormat: true}}
                        value={value.start}
                    />
                </Form.Field>
                <Form.Field colSpan={6} label={translate('sulu_admin.end')}>
                    <DatePicker
                        onChange={this.handleEndChange}
                        options={{dateFormat: true, timeFormat: true}}
                        value={value.end}
                    />
                </Form.Field>
            </Form>
        );
    }
}
