// @flow
import type {Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Cell from './Cell';
import HeaderCell from './HeaderCell';
import tableStyles from './table.scss';

export default class Row extends React.PureComponent {
    props: {
        children: Element<Cell | HeaderCell>,
        className?: string,
        onDoubleClick?: () => void,
    };

    handleDoubleClick = () => {
        if (this.props.onDoubleClick) {
            this.props.onDoubleClick();
        }
    };

    render() {
        const {className} = this.props;
        const rowClass = classNames(
            tableStyles.row,
            className,
        );

        return (
            <tr
                className={rowClass}
                onDoubleClick={this.handleDoubleClick}>
                {this.props.children}
            </tr>
        );
    }
}
