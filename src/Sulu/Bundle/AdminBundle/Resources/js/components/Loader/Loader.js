// @flow
import React from 'react';
import loaderStyles from './loader.scss';

export default class Loader extends React.PureComponent {
    render() {
        return (
            <div className={loaderStyles.spinner}>
                <div className={loaderStyles.doubleBounce1} />
                <div className={loaderStyles.doubleBounce2} />
            </div>
        );
    }
}
