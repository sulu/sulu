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
        isActive?: boolean,
    };

    render() {
        const {
            isActive,
        } = this.props;
        const rowClasses = classNames({
            [tableStyles.isActive]: isActive,
        });

        return (
            <tr className={rowClasses}>
                {this.props.children}
            </tr>
        );
    }
}
