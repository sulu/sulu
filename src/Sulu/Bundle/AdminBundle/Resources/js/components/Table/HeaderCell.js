// @flow
import type {Node} from 'react';
import React from 'react';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of a header cell */
    children?: Node,
};

export default class HeaderCell extends React.PureComponent<Props> {
    render() {
        return (
            <th className={tableStyles.headerCell}>
                {this.props.children}
            </th>
        );
    }
}
