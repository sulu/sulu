// @flow
import React from 'react';
import type {Node} from 'react';
import Divider from '../Divider';
import Grid from '../Grid';
import type {Colspan} from '../Grid';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    label?: string,
    colspan: Colspan,
|};

export default class Section extends React.Component<Props> {
    static defaultProps = {
        colspan: 12,
    };

    render() {
        const {children, label, colspan} = this.props;

        return (
            <Grid.Section className={gridStyles.gridSection} colspan={colspan}>
                {(label || colspan === 12) &&
                    <Grid.Item colspan={12}>
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
