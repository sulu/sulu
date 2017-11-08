// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../Icon';
import Loader from '../Loader';
import type {Button as ButtonProps} from './types';
import buttonStyles from './button.scss';

const LOADER_SIZE = 20;
const ICON_ARROW_DOWN = 'su-arrow-down';

export default class Button extends React.PureComponent<ButtonProps> {
    static defaultProps = {
        disabled: false,
        hasOptions: false,
        active: false,
    };

    handleOnClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            icon,
            size,
            asdf,
            skin,
            value,
            active,
            loading,
            disabled,
            hasOptions,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[skin],
            {
                [buttonStyles.active]: active,
                [buttonStyles[size]]: size,
                [buttonStyles[asdf]]: asdf,
            }
        );
        const buttonContent = this.props.children || value;

        return (
            <button
                disabled={disabled}
                className={buttonClass}
                onClick={this.handleOnClick}
                value={value}
            >
                {loading &&
                    <Loader size={LOADER_SIZE} className={buttonStyles.loader} />
                }
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
