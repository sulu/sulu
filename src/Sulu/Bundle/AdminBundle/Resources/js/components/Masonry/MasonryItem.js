// @flow
import type {ElementRef, Node} from 'react';
import React from 'react';
import masonryItemStyles from './masonryItem.scss';

type Props = {
    children: Node,
    itemRef?: ElementRef<'li'>,
};

export default class MasonryItem extends React.PureComponent<Props> {
    render() {
        const {
            itemRef,
            children,
        } = this.props;

        return (
            <li
                ref={itemRef}
                className={masonryItemStyles.masonryItem}>
                {children}
            </li>
        );
    }
}
