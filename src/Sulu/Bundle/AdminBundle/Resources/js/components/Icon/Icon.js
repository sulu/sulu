// @flow
import 'font-awesome/css/font-awesome.min.css';
import './sulu-icon.css';
import React from 'react';
import classNames from 'classnames';
import log from 'loglevel';
import iconStyles from './icon.scss';

type Props = {
    className?: string,
    onClick?: () => void,
    name: string,
    style?: Object,
};

function logInvalidIconWarning(name: string) {
    log.warn('Invalid icon given: "' + name + '"');
}

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
        const {className, name, onClick, style} = this.props;
        let fontClass = '';

        if (!name || name.length <= 0) {
            logInvalidIconWarning(name);

            return null;
        }

        switch (name.substr(0, 3)) {
            case 'su-':
                fontClass = null;
                break;
            case 'fa-':
                fontClass = 'fa';
                break;
            default:
                logInvalidIconWarning(name);

                return null;
        }

        const iconClass = classNames(
            fontClass ? fontClass : undefined,
            name,
            {
                [iconStyles.clickable]: onClick,
            },
            className
        );

        const onClickProperties = onClick
            ? {
                onClick: this.handleClick,
                role: 'button',
                tabIndex: 0,
            }
            : {};

        return (
            <span aria-label={name} className={iconClass} style={style} {...onClickProperties} />
        );
    }
}
