// @flow
import React from 'react';
import type {TableChildren} from './types';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of the table */
    children: TableChildren,
};

export default class Table extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <table className={tableStyles.table}>
                {children}
            </table>
        );
    }
}
