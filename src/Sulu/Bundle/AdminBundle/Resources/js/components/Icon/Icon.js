// @flow
import React from 'react';
import classNames from 'classnames';
import 'font-awesome/css/font-awesome.min.css';

export default class Icon extends React.PureComponent {
    props: {
        className: string,
        name: string,
    };

    render() {
        const className = classNames(
            this.props.className,
            'fa',
            'fa-' + this.props.name
        );

        return (
            <span className={className} />
        );
    }
}
