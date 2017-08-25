// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import tableStyles from './table.scss';

const ASCENDING_ICON = 'chevron-up';
const DESCENDING_ICON = 'chevron-down';

type Props = {
    children?: Node,
    /**
     * @ignore 
     * If set to true, the cell appears as a header-control-cell
     */
    isControl?: boolean,
    /** Called when column was clicked */
    onClick?: () => void,
    /** If set, an indicator will show up */
    sortMode: 'default' | 'ascending' | 'descending',
};

export default class HeaderCell extends React.PureComponent<Props> {
    static defaultProps = {
        isControl: false,
        sortMode: 'default',
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
            isControl,
        } = this.props;
        const headerCellClass = classNames(
            tableStyles.headerCell,
            {
                [tableStyles.headerControlCell]: isControl,
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
                        onClick={this.handleOnClick}>
                        <span>{children}</span>
                        {this.getSortModeIcon()}
                    </button>
                }
            </th>
        );
    }
}
