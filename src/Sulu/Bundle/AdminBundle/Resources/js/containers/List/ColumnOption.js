// @flow
import React from 'react';
import classNames from 'classnames';
import {SortableHandle} from 'react-sortable-hoc';
import Icon from '../../components/Icon';
import columnOptionsStyles from './columnOptions.scss';

const DRAG_ICON = 'su-more';

type Props = {
    label: string,
    onChange: (visibility: 'yes' | 'no', schemaKey: string) => void,
    schemaKey: string,
    visibility: 'always' | 'yes' | 'no',
};

const DragHandle = SortableHandle(() => {
    return (
        <span className={columnOptionsStyles.dragHandle}>
            <Icon name={DRAG_ICON} />
        </span>
    );
});

export default class ColumnOption extends React.Component<Props> {
    handleIconClick = () => {
        const {
            onChange,
            schemaKey,
            visibility,
        } = this.props;

        onChange(visibility === 'yes' ? 'no' : 'yes', schemaKey);
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
                    <Icon className={columnOptionsStyles.icon} name="su-eye" onClick={this.handleIconClick} />
                }
            </div>
        );
    }
}
