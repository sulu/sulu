// @flow
import React from 'react';
import tableStyles from './table.scss';
import type {RowProps} from './types';

export default class Row extends React.PureComponent<RowProps> {
    static defaultProps = {
        selected: false,
    };

    render() {
        const {
            children,
        } = this.props;

        return (
            <tr className={tableStyles.row}>
                {children}
            </tr>
        );
    }
}
