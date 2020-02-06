// @flow
import React from 'react';
import type {Node} from 'react';
import {SortableHandle} from 'react-sortable-hoc';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

const DRAG_ICON = 'su-more';

type Props<T> = {
    allowRemoveWhileDisabled: boolean,
    children: Node,
    disabled: boolean,
    id: T,
    index: number,
    onEdit?: (id: T) => void,
    onRemove?: (id: T) => void,
    sortable: boolean,
};

export default class Item<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        allowRemoveWhileDisabled: false,
        disabled: false,
        sortable: true,
    };

    createDragHandle() {
        const {sortable} = this.props;

        const handle = ({className, children}: Object) => (
            <span className={className}>{children}</span>
        );

        if (!sortable) {
            return handle;
        }

        return SortableHandle(handle);
    }

    handleEdit = () => {
        const {id, onEdit} = this.props;

        if (onEdit) {
            onEdit(id);
        }
    };

    handleRemove = () => {
        const {id, onRemove} = this.props;

        if (onRemove) {
            onRemove(id);
        }
    };

    render() {
        const {
            allowRemoveWhileDisabled,
            children,
            disabled,
            index,
            onEdit,
            onRemove,
            sortable,
        } = this.props;

        const DragHandle = this.createDragHandle();

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.disabled]: disabled,
            }
        );

        const dragHandleClass = classNames(
            itemStyles.dragHandle,
            {
                [itemStyles.sortable]: sortable,
            }
        );

        return (
            <div className={itemClass}>
                <DragHandle className={dragHandleClass}>
                    {sortable && <Icon name={DRAG_ICON} />}
                    <span className={itemStyles.index}>{index}</span>
                </DragHandle>
                <div className={itemStyles.content}>
                    {children}
                </div>
                <div className={itemStyles.buttons}>
                    {onEdit && !disabled &&
                        <button className={itemStyles.button} onClick={this.handleEdit} type="button">
                            <Icon name="su-pen" />
                        </button>
                    }
                    {onRemove && (!disabled || allowRemoveWhileDisabled) &&
                        <button className={itemStyles.button} onClick={this.handleRemove} type="button">
                            <Icon name="su-trash-alt" />
                        </button>
                    }
                </div>
            </div>
        );
    }
}
