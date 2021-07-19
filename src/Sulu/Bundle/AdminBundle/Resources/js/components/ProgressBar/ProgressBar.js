// @flow
import React from 'react';
import classNames from 'classnames';
import styles from './progressBar.scss';

type Props = {
    max: number,
    style: 'progress' | 'success' | 'error' | 'warning',
    value: number,
}

class ProgressBar extends React.PureComponent<Props> {
    static defaultProps = {
        style: 'progress',
    };

    render() {
        const {value, max, style} = this.props;

        const className = classNames(styles.progressBar, styles[style]);

        return (
            <progress className={className} max={max} value={value}>
                {(value / max) * 100}%
            </progress>
        );
    }
}

export default ProgressBar;
