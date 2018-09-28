// @flow
import React from 'react';
import classNames from 'classnames';
import {SortableHandle} from 'react-sortable-hoc';
import {Icon} from '../../components';
import columnOptionsStyles from './columnOptions.scss';

const DRAG_ICON = 'su-more';

type Props = {
    schemaKey: string,
    label: string,
    visibility: 'always' | 'yes' | 'no',
    onChange: (schemaKey: string, visibility: 'yes' | 'no') => void,
};

const DragHandle = SortableHandle(() => {
    return (
        <span className={columnOptionsStyles.dragHandle}>
            <Icon name={DRAG_ICON} />
        </span>
    );
});

export default class ColumnOptionComponent extends React.Component<Props> {
    handleIconClick = () => {
        const {
            onChange,
            schemaKey,
            visibility,
        } = this.props;

        onChange(schemaKey, visibility === 'yes' ? 'no' : 'yes');
    };

    render() {
        const {
            label,
            visibility,
        } = this.props;

        const className = classNames(
            columnOptionsStyles.columnOption,
            {
                [columnOptionsStyles.columnOptionDisabled]: visibility === 'no',
            }
        );

        return (
            <div className={className}>
                <DragHandle />
                <span className={columnOptionsStyles.label}>{label}</span>
                {visibility !== 'always' &&
                    <Icon className={columnOptionsStyles.icon} name={'su-eye'} onClick={this.handleIconClick} />
                }
            </div>
        );
    }
}
