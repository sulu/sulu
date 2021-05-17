// @flow
import React from 'react';
import classNames from 'classnames';
import Item from './Item';
import Section from './Section';
import gridStyles from './grid.scss';
import type {Node} from 'react';

type Props = {
    children: Node,
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
