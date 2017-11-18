// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ItemButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    index?: number,
    children?: ChildrenArray<Element<typeof Item>>,
    buttons?: Array<ItemButtonConfig>,
    active: boolean,
    onActive?: (index?: number) => void,
    onItemClick?: (id: string | number) => void,
    toolbarItems: Array<ToolbarItemConfig>,
    loading: boolean,
};

export default class Column extends React.Component<Props> {
    static defaultProps = {
        active: false,
        toolbarItems: [],
        loading: false,
    };

    cloneItems = (originalItems?: ChildrenArray<Element<typeof Item>>) => {
        if (!originalItems) {
            return null;
        }

        const {buttons, onItemClick, index} = this.props;

        if (0 === originalItems.length) {
            return (
                <div className={columnListStyles.emptyMessage}>
                    <div className={columnListStyles.icon}><Icon name="coffee" /></div>
                    <div>No children dude..</div>
                </div>
            );
        }

        return React.Children.map(originalItems, (item) => {
            return React.cloneElement(
                item,
                {
                    columnIndex: index,
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
        const {children, active, index, toolbarItems, loading} = this.props;

        const columnContainerClass = classNames(
            columnListStyles.columnContainer,
            {
                [columnListStyles.active]: active,
            }
        );

        const columnClass = classNames(
            columnListStyles.column,
            {
                [columnListStyles.centerContent]: (!children || 0 === children.length) || loading,
            }
        );

        let items = null;

        if (loading) {
            items = <Loader />;
        } else {
            items = this.cloneItems(children);
        }

        return (
            <div onMouseEnter={this.handleMouseEnter} className={columnContainerClass}>
                <Toolbar active={active} columnIndex={index} toolbarItems={toolbarItems} />
                <div className={columnClass}>
                    {items}
                </div>
            </div>
        );
    }
}

