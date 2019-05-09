// @flow
import React from 'react';
import type {Node} from 'react';
import Divider from '../Divider';
import Grid from '../Grid';
import type {ColSpan} from '../Grid';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    colSpan: ColSpan,
    label?: string,
|};

export default class Section extends React.Component<Props> {
    static defaultProps = {
        colSpan: 12,
    };

    render() {
        const {children, label, colSpan} = this.props;

        return (
            <Grid.Section className={gridStyles.gridSection} colSpan={colSpan}>
                {(label || colSpan === 12) &&
                    <Grid.Item colSpan={12}>
                        <Divider>
                            {label}
                        </Divider>
                    </Grid.Item>
                }
                {children}
            </Grid.Section>
        );
    }
}
