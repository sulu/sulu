// @flow
import React from 'react';
import classNames from 'classnames';
import ghostIndicatorStyles from './ghostIndicator.scss';

type Props = {|
    className?: string,
    locale: string,
|};

export default class GhostIndicator extends React.Component<Props> {
    render() {
        const {className} = this.props;

        const ghostIndicatorClass = classNames(
            ghostIndicatorStyles.ghostIndicator,
            className
        );

        return <span className={ghostIndicatorClass}>{this.props.locale}</span>;
    }
}
