// @flow
import 'font-awesome/css/font-awesome.min.css';
import React from 'react';
import classNames from 'classnames';

type Props = {
    className?: string,
    onClick?: () => void,
    name: string,
};

export default class Icon extends React.PureComponent<Props> {
    render() {
        const {className, name, onClick} = this.props;
        const classes = classNames(className, 'fa', 'fa-' + name);

        return (
            <span className={classes} aria-hidden={true} onClick={onClick} />
        );
    }
}
