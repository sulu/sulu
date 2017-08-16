// @flow
import type {Element} from 'react';
import React from 'react';
import Row from './Row';
import tableStyles from './table.scss';

export default class Header extends React.PureComponent {
    props: {
        children: Element<Row>,
    };

    render() {
        return (
            <thead className={tableStyles.header}>
                {this.props.children}
            </thead>
        );
    }
}
