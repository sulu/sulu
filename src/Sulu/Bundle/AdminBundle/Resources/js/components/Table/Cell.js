// @flow
import type {Element} from 'react';
import React from 'react';
import tableStyles from './table.scss';

export default class Cell extends React.PureComponent {
    props: {
        children: Element<*>,
    };

    render() {
        return (
            <td className={tableStyles.cell}>
                {this.props.children}
            </td>
        );
    }
}
