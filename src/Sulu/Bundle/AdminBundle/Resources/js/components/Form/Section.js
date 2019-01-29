// @flow
import React from 'react';
import type {Node} from 'react';
import Divider from '../Divider';
import Grid from '../Grid';
import type {Size} from '../Grid';
import gridStyles from './grid.scss';

type Props = {|
    children: Node,
    label?: string,
    size: Size,
|};

export default class Section extends React.Component<Props> {
    static defaultProps = {
        size: 12,
    };

    render() {
        const {children, label, size} = this.props;

        return (
            <Grid.Section className={gridStyles.gridSection} size={size}>
                {(label || size === 12) &&
                    <Grid.Item size={12}>
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
