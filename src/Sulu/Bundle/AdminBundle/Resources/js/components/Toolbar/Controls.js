// @flow
import type {Node} from 'react';
import React from 'react';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: Node,
};

export default class Controls extends React.PureComponent<Props> {
    render() {
        return (
            <div className={toolbarStyles.controls}>
                {this.props.children}
            </div>
        );
    }
}
