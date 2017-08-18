// @flow
import type {Node} from 'react';
import classNames from 'classnames';
import React from 'react';
import tableStyles from './table.scss';

type Props = {
    children: Node,
    className?: string,
};

export default class HeaderCell extends React.PureComponent<Props> {
    render() {
        const {className} = this.props;
        const tableHeaderClass = classNames(
            tableStyles.headerCell,
            className,
        );

        return (
            <th className={tableHeaderClass}>
                {this.props.children}
            </th>
        );
    }
}
