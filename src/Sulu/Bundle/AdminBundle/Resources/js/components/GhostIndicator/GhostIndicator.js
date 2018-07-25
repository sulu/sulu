// @flow
import React from 'react';
import ghostIndicatorStyles from './ghostIndicator.scss';

type Props = {
    locale: string,
};

export default class GhostIndicator extends React.Component<Props> {
    render() {
        return <span className={ghostIndicatorStyles.ghostIndicator}>{this.props.locale}</span>;
    }
}
