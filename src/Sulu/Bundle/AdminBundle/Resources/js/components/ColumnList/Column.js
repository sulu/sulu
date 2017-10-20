// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ButtonConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    index: number,
    children: ChildrenArray<Element<typeof Item>>,
    buttons?: Array<ButtonConfig>,
    active: boolean,
    onActive: (index: number) => void,
};

export default class ColumnList extends React.PureComponent<Props> {
    cloneItems = (originalItems: ChildrenArray<Element<typeof Item>>) => {
        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    buttons: this.props.buttons,
                }
            );
        });
    };

    handleMouseEnter = () => {
        if (!this.props.onActive) {
            return;
        }

        this.props.onActive(this.props.index);
    };

    render() {
        const {children, active} = this.props;

        const columnContainerClass = classNames(
            columnListStyles.columnContainer,
            {
                [columnListStyles.isActive]: active,
            }
        );

        return (
            <div onMouseEnter={this.handleMouseEnter} className={columnContainerClass}>
                <Toolbar active={active} />
                <div className={columnListStyles.column}>
                    {this.cloneItems(children)}
                </div>
            </div>
        );
    }
}

