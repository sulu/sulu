// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of a cell */
    children?: Node,
};

export default class Cell extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <td className={tableStyles.cell}>
                {children}
            </td>
        );
    }
}
