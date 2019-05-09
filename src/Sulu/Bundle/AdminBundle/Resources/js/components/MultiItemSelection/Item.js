// @flow
import React from 'react';
import type {Node} from 'react';
import {SortableHandle} from 'react-sortable-hoc';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

const DRAG_ICON = 'su-more';
const REMOVE_ICON = 'su-trash-alt';

type Props = {
    children: Node,
    id: string | number,
    index: number,
    onRemove?: (id: string | number) => void,
    sortable: boolean,
};

export default class Item extends React.PureComponent<Props> {
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

    handleRemove = () => {
        if (this.props.onRemove) {
            this.props.onRemove(this.props.id);
        }
    };

    render() {
        const {
            children,
            index,
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
                {onRemove &&
                    <button
                        className={itemStyles.removeButton}
                        onClick={this.handleRemove}
                        type="button"
                    >
                        <Icon name={REMOVE_ICON} />
                    </button>
                }
            </div>
        );
    }
}
