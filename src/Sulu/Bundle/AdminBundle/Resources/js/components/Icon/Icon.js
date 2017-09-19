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
        const iconClass = classNames(className, 'fa', 'fa-' + name);

        const onClickProperties = onClick
            ? {
                onClick,
                role: 'button',
                tabIndex: 0,
            }
            : {};

        return (
            <span className={iconClass} aria-label={name} {...onClickProperties} />
        );
    }
}
