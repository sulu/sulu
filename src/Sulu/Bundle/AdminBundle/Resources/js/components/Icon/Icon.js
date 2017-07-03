// @flow
import React from 'react';
import classNames from 'classnames';
import fontAwesomeStyle from 'font-awesome/css/font-awesome.min.css';

export default class Icon extends React.PureComponent {
    props: {
        className: string,
        name: string,
    };

    render() {
        const className = classNames(
            this.props.className,
            fontAwesomeStyle['fa'],
            fontAwesomeStyle['fa-' + this.props.name]
        );

        return (
            <span className={className} />
        );
    }
}
