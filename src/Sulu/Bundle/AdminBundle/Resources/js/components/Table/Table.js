// @flow
import React from 'react';
import classNames from 'classnames';
import type {TableChildren} from './types';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of the table */
    children: TableChildren,
    /** CSS classes to apply custom styles */
    className?: string,
};

export default class Table extends React.PureComponent<Props> {
    render() {
        const {
            children,
            className,
        } = this.props;
        const tableClass = classNames(
            tableStyles.tableContainer,
            className,
        );

        return (
            <div className={tableClass}>
                <table className={tableStyles.table}>
                    {children}
                </table>
            </div>
        );
    }
}
