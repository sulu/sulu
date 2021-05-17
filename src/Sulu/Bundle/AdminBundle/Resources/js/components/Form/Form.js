// @flow
import React from 'react';
import Grid from '../Grid';
import Field from './Field';
import Section from './Section';
import gridStyles from './grid.scss';
import type {Node} from 'react';

type Props = {|
    children: Node,
    skin?: 'light' | 'dark',
|};

export default class Form extends React.Component<Props> {
    static Field = Field;
    static Section = Section;

    cloneChildren = () => {
        const {children, skin} = this.props;

        return React.Children.map(children, (child) => {
            if (!child) {
                return null;
            }

            return React.cloneElement(
                child,
                {
                    skin,
                }
            );
        });
    };

    render() {
        return (
            <Grid className={gridStyles.grid}>
                {this.cloneChildren()}
            </Grid>
        );
    }
}
