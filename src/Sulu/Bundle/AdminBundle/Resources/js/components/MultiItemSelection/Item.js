// @flow
import React from 'react';
import type {Node} from 'react';
import {SortableHandle} from 'react-sortable-hoc';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

const DRAG_ICON = 'su-more';

type Props<T> = {
    children: Node,
    id: T,
    index: number,
    onEdit?: (id: T) => void,
    onRemove?: (id: T) => void,
    sortable: boolean,
};

export default class Item<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
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
            children,
            index,
            onEdit,
            onRemove,
            sortable,
        } = this.props;

        const DragHandle = this.createDragHandle();

        const dragHandleClass = classNames(
            itemStyles.dragHandle,
            {
                [itemStyles.sortable]: sortable,
            }
        );

        return (
            <div className={itemStyles.item}>
                <DragHandle className={dragHandleClass}>
                    {sortable && <Icon name={DRAG_ICON} />}
                    <span className={itemStyles.index}>{index}</span>
                </DragHandle>
                <div className={itemStyles.content}>
                    {children}
                </div>
                <div className={itemStyles.buttons}>
                    {onEdit &&
                        <button className={itemStyles.button} onClick={this.handleEdit} type="button">
                            <Icon name="su-pen" />
                        </button>
                    }
                    {onRemove &&
                        <button className={itemStyles.button} onClick={this.handleRemove} type="button">
                            <Icon name="su-trash-alt" />
                        </button>
                    }
                </div>
            </div>
        );
    }
}
