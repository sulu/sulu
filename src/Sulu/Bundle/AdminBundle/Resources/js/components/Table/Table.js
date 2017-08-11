// @flow
import type {Element} from 'react';
import React from 'react';

export default class Table extends React.PureComponent {
    props: {
        children: Element<*>,
    };

    render() {
        return (
            <table>
                <tbody>
                    {this.props.children}
                </tbody>
            </table>
        );
    }
}
