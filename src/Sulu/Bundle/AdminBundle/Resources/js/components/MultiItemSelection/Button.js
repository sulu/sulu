// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import buttonStyles from './button.scss';
import type {Button as ButtonConfig} from './types';

type Props = ButtonConfig & {
    location: 'left' | 'right',
};

export default class Button extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            disabled,
            icon,
            location,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[location]
        );

        return (
            <button
                className={buttonClass}
                disabled={disabled}
                onClick={this.handleClick}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}
