// @flow
import type {Element} from 'react';
import React from 'react';
import tableStyles from './table.scss';

export default class HeaderCell extends React.PureComponent {
    props: {
        children: Element<*>,
    };

    render() {
        return (
            <th className={tableStyles.headerCell}>
                {this.props.children}
            </th>
        );
    }
}
