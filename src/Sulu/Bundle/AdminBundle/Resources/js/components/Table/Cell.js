// @flow
import type {Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

export default class Cell extends React.PureComponent {
    props: {
        children: Element<*>,
        className?: string,
    };

    render() {
        const {
            className,
        } = this.props;
        const cellClass = classNames(
            tableStyles.cell,
            className,
        );

        return (
            <td className={cellClass}>
                {this.props.children}
            </td>
        );
    }
}
