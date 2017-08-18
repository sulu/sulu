// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

type Props = {
    children: Node,
    className?: string,
};

export default class Cell extends React.PureComponent<Props> {
    render() {
        const {
            children,
            className,
        } = this.props;
        const cellClass = classNames(
            tableStyles.cell,
            className,
        );

        return (
            <td className={cellClass}>
                {children}
            </td>
        );
    }
}
