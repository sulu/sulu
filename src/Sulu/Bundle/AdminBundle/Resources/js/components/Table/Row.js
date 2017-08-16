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
        selectable?: boolean,
    };

    render() {
        const {
            selectable,
        } = this.props;
        const rowClasses = classNames({
            [tableStyles.row]: true,
            [tableStyles.selectable]: selectable,
        });

        return (
            <tr className={rowClasses}>
                {this.props.children}
            </tr>
        );
    }
}
