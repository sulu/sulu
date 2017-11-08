// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Item from './Item';
import Section from './Section';
import gridStyles from './grid.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item | typeof Section>>,
};

export default class Grid extends React.PureComponent<Props> {
    static Item = Item;

    static Section = Section;

    render() {
        const {children} = this.props;

        return (
            <div className={gridStyles.grid}>
                {children}
            </div>
        );
    }
}
