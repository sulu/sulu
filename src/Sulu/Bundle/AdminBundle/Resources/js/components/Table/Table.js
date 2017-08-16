// @flow
import type {Element} from 'react';
import React from 'react';
import Header from './Header';
import Body from './Body';
import tableStyles from './table.scss';

export default class Table extends React.PureComponent {
    props: {
        children: Element<Header | Body>,
    };

    render() {
        return (
            <div className={tableStyles.tableContainer}>
                <table className={tableStyles.table}>
                    {this.props.children}
                </table>
            </div>
        );
    }
}
