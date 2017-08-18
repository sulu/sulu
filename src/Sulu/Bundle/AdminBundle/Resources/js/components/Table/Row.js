// @flow
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';
import type {RowProps} from './types';

export default class Row extends React.PureComponent<RowProps> {
    static defaultProps = {
        selected: false,
    };

    render() {
        const {
            children,
            className,
        } = this.props;
        const rowClass = classNames(
            tableStyles.row,
            className,
        );

        return (
            <tr className={rowClass}>
                {children}
            </tr>
        );
    }
}
