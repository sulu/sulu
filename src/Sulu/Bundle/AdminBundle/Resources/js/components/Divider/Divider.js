// @flow
import React from 'react';
import dividerStyles from './divider.scss';

type Props = {
    children?: string,
};

export default class Divider extends React.PureComponent<Props> {
    render() {
        const {children} = this.props;

        return (
            <div className={dividerStyles.divider}>
                {children}
            </div>
        );
    }
}
