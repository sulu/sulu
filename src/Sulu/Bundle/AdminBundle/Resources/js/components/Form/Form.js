// @flow
import React from 'react';
import type {Node} from 'react';
import Grid from '../Grid';
import Field from './Field';
import Section from './Section';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
|};

export default class Form extends React.Component<Props> {
    static Field = Field;
    static Section = Section;

    render() {
        const {children} = this.props;

        return (
            <Grid className={gridStyles.grid}>
                {children}
            </Grid>
        );
    }
}
