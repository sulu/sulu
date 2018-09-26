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

export default class ColumnOptionComponent extends React.Component<Props> {
    handleIconClick = () => {
        const {
            onChange,
            schemaKey,
            visibility,
        } = this.props;

        onChange(schemaKey, visibility === 'yes' ? 'no' : 'yes');
    };

    createDragHandle() {
        const handle = ({className, children}: Object) => (
            <span className={className}>{children}</span>
        );

        return SortableHandle(handle);
    }

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

        const DragHandle = this.createDragHandle();

        return (
            <div className={className}>
                <DragHandle className={columnOptionsStyles.dragHandle}>
                    <Icon name={DRAG_ICON} />
                </DragHandle>
                <span className={columnOptionsStyles.label}>{label}</span>
                {visibility !== 'always' &&
                    <Icon className={columnOptionsStyles.icon} name={'su-eye'} onClick={this.handleIconClick} />
                }
            </div>
        );
    }
}
