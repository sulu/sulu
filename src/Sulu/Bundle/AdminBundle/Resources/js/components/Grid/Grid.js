// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import classNames from 'classnames';
import Item from './Item';
import Section from './Section';
import gridStyles from './grid.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item | typeof Section>>,
    className?: string,
};

export default class Grid extends React.PureComponent<Props> {
    static Item = Item;

    static Section = Section;

    render() {
        const {children, className} = this.props;

        const gridClass = classNames([
            gridStyles.grid,
            className,
        ]);

        return (
            <div className={gridClass}>
                {children}
            </div>
        );
    }
}
