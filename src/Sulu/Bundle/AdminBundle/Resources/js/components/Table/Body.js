// @flow
import type {Element} from 'react';
import React from 'react';
import Row from './Row';

export default class Body extends React.PureComponent {
    props: {
        children: Element<Row>,
    };

    render() {
        return (
            <tbody>{this.props.children}</tbody>
        );
    }
}
