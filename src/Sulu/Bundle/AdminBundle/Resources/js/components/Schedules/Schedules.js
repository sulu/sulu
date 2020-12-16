// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import BlockCollection from '../BlockCollection';
import {translate} from '../../utils/Translator';
import FixedSchedule from './FixedSchedule';
import WeeklySchedule from './WeeklySchedule';
import type {ScheduleEntry, ScheduleType} from './types';

type Props = {|
    disabled: boolean,
    onChange: (value: Array<ScheduleEntry>) => void,
    value: ?Array<ScheduleEntry>,
|};

@observer
class Schedules extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    @computed get types(): {[key: ScheduleType]: string} {
        return {
            weekly: translate('sulu_admin.weekly'),
            fixed: translate('sulu_admin.fixed'),
        };
    }

    handleBlockChange = (index: number, newValue: ScheduleEntry) => {
        const {onChange, value} = this.props;

        onChange([
            ...(value ? value.slice(0, index) : []),
            newValue,
            ...(value ? value.slice(index + 1) : []),
        ]);
    };

    renderBlockContent = (value: ScheduleEntry, type: ScheduleType, index: number) => {
        switch (value.type) {
            case 'weekly':
                return <WeeklySchedule index={index} onChange={this.handleBlockChange} value={value} />;
            case 'fixed':
                return <FixedSchedule index={index} onChange={this.handleBlockChange} value={value} />;
            default:
                throw new Error('"' + type + '" is not a valid schedule type!');
        }
    };

    render() {
        const {disabled, onChange, value} = this.props;

        return (
            // $FlowFixMe
            <BlockCollection
                collapsable={false}
                defaultType="fixed"
                disabled={disabled}
                movable={false}
                onChange={onChange}
                renderBlockContent={this.renderBlockContent}
                // $FlowFixMe
                types={this.types}
                value={value || []}
            />
        );
    }
}

export default Schedules;
