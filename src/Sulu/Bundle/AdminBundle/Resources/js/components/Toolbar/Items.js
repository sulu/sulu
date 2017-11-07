// @flow
import type {ChildrenArray} from 'react';
import React from 'react';
import type {Item} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: ChildrenArray<Item>,
};

export default class Items extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <ul className={toolbarStyles.items}>
                {children && React.Children.map(children, (item, index) => {
                    return <li key={index}>{item}</li>;
                })}
            </ul>
        );
    }
}
