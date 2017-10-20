// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';
import Element from './Element';
import Children from './Children';

type Props = {
    children: ChildrenArray<Element<typeof Element | typeof Children>>,
};

@observer
export default class Node extends React.PureComponent<Props> {

    static Node = Node;

    static Children = Children;

    render() {
        return (
            <li>
                {this.props.children}
            </li>
        );
    }
}
