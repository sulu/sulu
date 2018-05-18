// @flow
import React from 'react';
import classNames from 'classnames';
import loaderStyles from './loader.scss';

type Props = {
    size: number,
    className?: string,
};

export default class Loader extends React.Component<Props> {
    static defaultProps = {
        size: 40,
    };

    render() {
        const {
            size,
            className,
        } = this.props;
        const dimensionStyle = {
            width: size,
            height: size,
        };
        const loaderClass = classNames(
            loaderStyles.spinner,
            className
        );

        return (
            <div className={loaderClass} style={dimensionStyle}>
                <div className={loaderStyles.doubleBounce1} />
                <div className={loaderStyles.doubleBounce2} />
            </div>
        );
    }
}
