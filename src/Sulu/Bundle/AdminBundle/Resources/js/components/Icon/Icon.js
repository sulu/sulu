// @flow
import 'font-awesome/css/font-awesome.min.css';
import React from 'react';
import classNames from 'classnames';
import iconStyles from './icon.scss';

type Props = {
    className?: string,
    onClick?: () => void,
    name: string,
};

export default class Icon extends React.PureComponent<Props> {
    handleClick = (event: SyntheticEvent<HTMLElement>) => {
        const {onClick} = this.props;

        if (!onClick) {
            return;
        }

        event.stopPropagation();
        onClick();
    };

    render() {
        const {className, name, onClick} = this.props;
        const iconClass = classNames(
            className,
            'fa',
            'fa-' + name,
            {
                [iconStyles.clickable]: onClick,
            }
        );

        const onClickProperties = onClick
            ? {
                onClick: this.handleClick,
                role: 'button',
                tabIndex: 0,
            }
            : {};

        return (
            <span className={iconClass} aria-label={name} {...onClickProperties} />
        );
    }
}
