// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import Item from './Item';
import type {ItemButtonConfig} from './types';
import columnStyles from './column.scss';

type Props = {
    buttons?: Array<ItemButtonConfig>,
    children?: ChildrenArray<Element<typeof Item>>,
    index?: number,
    loading: boolean,
    onActive?: (index?: number) => void,
    onItemClick?: (id: string | number) => void,
};

export default class Column extends React.Component<Props> {
    static defaultProps = {
        loading: false,
    };

    cloneItems = (originalItems?: ChildrenArray<Element<typeof Item>>) => {
        if (!originalItems) {
            return null;
        }

        const {buttons, onItemClick} = this.props;

        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    buttons: buttons,
                    onClick: onItemClick,
                }
            );
        });
    };

    handleMouseEnter = () => {
        const {index, onActive} = this.props;

        if (!onActive) {
            return;
        }

        onActive(index);
    };

    render() {
        const {children, loading} = this.props;

        const columnClass = classNames(
            columnStyles.column,
            {
                [columnStyles.loading]: loading,
            }
        );

        return (
            <div className={columnClass} onMouseEnter={this.handleMouseEnter}>
                {loading ? <Loader /> : this.cloneItems(children)}
            </div>
        );
    }
}
