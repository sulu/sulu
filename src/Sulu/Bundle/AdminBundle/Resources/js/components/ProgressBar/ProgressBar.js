// @flow
import React from 'react';
import classNames from 'classnames';
import progressBarStyles from './progressBar.scss';

type Props = {
    max: number,
    type: 'progress' | 'success' | 'error' | 'warning',
    value: number,
}

class ProgressBar extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'progress',
    };

    render() {
        const {value, max, type} = this.props;

        const className = classNames(progressBarStyles.progressBar, progressBarStyles[type]);

        return (
            <progress className={className} max={max} value={value}>
                {(value / max) * 100}%
            </progress>
        );
    }
}

export default ProgressBar;
