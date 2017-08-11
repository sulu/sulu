// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../../components/Icon';
import type {Button as ButtonProps} from './types';
import buttonStyles from './button.scss';

const ICON_ARROW_DOWN = 'chevron-down';

export default class Button extends React.PureComponent {
    props: ButtonProps;

    static defaultProps = {
        disabled: false,
        hasOptions: false,
        isActive: false,
    };

    handleOnClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            icon,
            size,
            value,
            disabled,
            isActive,
            hasOptions,
        } = this.props;
        const buttonClasses = classNames({
            [buttonStyles.button]: true,
            [buttonStyles.isActive]: isActive,
            [buttonStyles[size]]: size,
        });
        const buttonContent = this.props.children || value;

        return (
            <button
                disabled={disabled}
                className={buttonClasses}
                onClick={this.handleOnClick}
                value={value}>
                {icon &&
                    <Icon name={icon} className={buttonStyles.icon} />
                }
                {buttonContent &&
                    <span className={buttonStyles.label}>{buttonContent}</span>
                }
                {hasOptions &&
                    <Icon name={ICON_ARROW_DOWN} className={buttonStyles.dropdownIcon} />
                }
            </button>
        );
    }
}
