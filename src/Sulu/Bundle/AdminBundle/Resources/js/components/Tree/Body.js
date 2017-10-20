// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';
import Node from './Node';
import Element from './Element';
import Children from './Children';

type Props = {
    children: ChildrenArray<Element<typeof Node>>,
};

@observer
export default class Tree extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    static Node = Node;

    static Element = Element;

    static Children = Children;

    render() {
        const {children} = this.props;
        let node;

        React.Children.forEach(children, (child: Element<typeof Node>) => {
            switch (child.type) {
                case Node:
                    node = child;
                    break;
                default:
                    throw new Error(
                        'The Tree Body component only accepts the following children types: ' +
                        [Node.name].join(', ') +
                        ' given: ' + child.type
                    );
            }
        });

        return (
            <ul>
                {children}
            </ul>
        );
    }
}
