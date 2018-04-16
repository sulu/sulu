// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import tableStyles from './table.scss';

const ASCENDING_ICON = 'su-angle-up';
const DESCENDING_ICON = 'su-angle-down';

type Props = {
    children?: Node,
    className?: string,
    /** Called when column was clicked */
    onClick?: () => void,
    /** If set, an indicator will show up */
    sortMode: 'none' | 'ascending' | 'descending',
};

export default class HeaderCell extends React.PureComponent<Props> {
    static defaultProps = {
        sortMode: 'none',
    };

    getSortModeIcon = () => {
        const {sortMode} = this.props;

        switch (sortMode) {
            case 'ascending':
                return (<Icon name={ASCENDING_ICON} className={tableStyles.headerCellSortIcon} />);
            case 'descending':
                return (<Icon name={DESCENDING_ICON} className={tableStyles.headerCellSortIcon} />);
            default:
                return null;
        }
    };

    handleOnClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
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
                        {this.getSortModeIcon()}
                    </button>
                }
            </th>
        );
    }
}
