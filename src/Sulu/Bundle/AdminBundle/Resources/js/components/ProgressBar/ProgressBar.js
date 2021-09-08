// @flow
import React from 'react';
import classNames from 'classnames';
import progressBarStyles from './progressBar.scss';

type Props = {
    max: number,
    skin: 'progress' | 'success' | 'error' | 'warning',
    value: number,
}

class ProgressBar extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'progress',
    };

    get max(): number {
        const {max} = this.props;

        if (max < 1) {
            return 1;
        }

        return max;
    }

    get value(): number {
        const {value} = this.props;

        if (value < 0) {
            return 0;
        }

        if (value > this.max) {
            return this.max;
        }

        return value;
    }

    render() {
        const {skin} = this.props;

        const className = classNames(progressBarStyles.progressBar, progressBarStyles[skin]);

        return (
            <progress className={className} max={this.max} value={this.value}>
                {(this.value / this.max) * 100}%
            </progress>
        );
    }
}

export default ProgressBar;
