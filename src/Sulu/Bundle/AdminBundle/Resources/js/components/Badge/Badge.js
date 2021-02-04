// @flow
import React from 'react';
import badgeStyles from './badge.scss';

type Props = {|
    children: string,
|};

export default class Badge extends React.PureComponent<Props> {
    render() {
        const {children} = this.props;

        return (
            <div className={badgeStyles.badge}>
                {children}
            </div>
        );
    }
}
