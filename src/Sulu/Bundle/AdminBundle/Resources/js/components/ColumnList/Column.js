// @flow
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import Item from './Item';
import columnStyles from './column.scss';
import type {ChildrenArray, Element} from 'react';

type Props = {|
    children?: ChildrenArray<Element<typeof Item>>,
    index?: number,
    loading: boolean,
    onActive?: ?(index?: number) => void,
    onItemClick?: ?(id: string | number) => void,
    onItemDoubleClick?: ?(id: string | number) => void,
    /** @ignore */
    scrolling: boolean,
|};

export default class Column extends React.Component<Props> {
    static defaultProps = {
        loading: false,
        scrolling: false,
    };

    cloneItems = (originalItems?: ChildrenArray<Element<typeof Item>>) => {
        if (!originalItems) {
            return null;
        }

        const {onItemClick, onItemDoubleClick} = this.props;

        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    onClick: onItemClick,
                    onDoubleClick: onItemDoubleClick,
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
        const {children, loading, scrolling} = this.props;

        const columnClass = classNames(
            columnStyles.column,
            {
                [columnStyles.scrolling]: scrolling,
            }
        );

        return (
            <div className={columnClass} onMouseEnter={this.handleMouseEnter} role="button">
                {loading ?
                    <div className={columnStyles.loader}>
                        <Loader />
                    </div>
                    : this.cloneItems(children)
                }
            </div>
        );
    }
}
