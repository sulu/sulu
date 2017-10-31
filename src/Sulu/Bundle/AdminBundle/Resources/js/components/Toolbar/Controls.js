// @flow
import type {Node} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: Node,
};

@observer
export default class Controls extends React.PureComponent<Props> {
    render() {
        return (
            <div className={toolbarStyles.controls}>
                {this.props.children}
            </div>
        );
    }
}
