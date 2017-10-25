// @flow
import React from 'react';
import classNames from 'classnames';
import loaderStyles from './loader.scss';

type Props = {
    className?: string,
};

export default class Loader extends React.PureComponent<Props> {
    render() {
        const {className} = this.props;
        const loaderClass = classNames(loaderStyles.spinner, className);

        return (
            <div className={loaderClass}>
                <div className={loaderStyles.doubleBounce1} />
                <div className={loaderStyles.doubleBounce2} />
            </div>
        );
    }
}
