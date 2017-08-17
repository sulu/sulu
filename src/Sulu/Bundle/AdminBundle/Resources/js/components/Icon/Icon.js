// @flow
import 'font-awesome/css/font-awesome.min.css';
import React from 'react';
import classNames from 'classnames';

type Props = {
    className: string,
    name: string,
};

export default class Icon extends React.PureComponent<Props> {
    render() {
        const className = classNames(
            this.props.className,
            'fa',
            'fa-' + this.props.name
        );

        return (
            <span className={className} aria-hidden={true} />
        );
    }
}
