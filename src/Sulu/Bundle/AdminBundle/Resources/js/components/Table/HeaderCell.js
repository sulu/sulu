// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import tableStyles from './table.scss';
import type {SortOrder} from './types';

const ASCENDING_ICON = 'su-angle-up';
const DESCENDING_ICON = 'su-angle-down';

type Props = {|
    children?: Node,
    className?: string,
    name?: string,
    /** Called when column was clicked */
    onClick?: (sortColumn: string, sortOrder: SortOrder) => void, // TODO extract order to own type file
    /** If set, an indicator will show up */
    sortOrder?: ?SortOrder,
|};

export default class HeaderCell extends React.PureComponent<Props> {
    getSortOrderIcon = () => {
        const {sortOrder} = this.props;

        switch (sortOrder) {
            case 'asc':
                return (<Icon name={ASCENDING_ICON} className={tableStyles.headerCellSortIcon} />);
            case 'desc':
                return (<Icon name={DESCENDING_ICON} className={tableStyles.headerCellSortIcon} />);
            default:
                return null;
        }
    };

    handleOnClick = () => {
        const {name, onClick, sortOrder} = this.props;
        if (onClick && name) {
            onClick(name, 'asc' === sortOrder ? 'desc' : 'asc');
        }
    };

    render() {
        const {
            onClick,
            children,
            className,
        } = this.props;
        const headerCellClass = classNames(
            className,
            tableStyles.headerCell,
            {
                [tableStyles.clickable]: !!onClick,
            }
        );

        return (
            <th className={headerCellClass}>
                {!onClick &&
                    <span>{children}</span>
                }
                {onClick &&
                    <button
                        onClick={this.handleOnClick}
                    >
                        <span>{children}</span>
                        {this.getSortOrderIcon()}
                    </button>
                }
            </th>
        );
    }
}
