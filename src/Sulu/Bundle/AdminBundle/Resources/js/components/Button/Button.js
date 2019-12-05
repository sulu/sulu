// @flow
import React from 'react';
import type {ElementRef, Node} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import buttonStyles from './button.scss';
import type {ButtonSkin} from './types';

const LOADER_SIZE = 25;

type Props<T> = {|
    active: boolean,
    activeClassName?: string,
    buttonRef?: (ref: ?ElementRef<'button'>) => void,
    children?: Node,
    className?: string,
    disabled: boolean,
    icon?: string,
    iconClassName?: string,
    loading: boolean,
    onClick?: (value: T) => void,
    showDropdownIcon: boolean,
    size: 'small' | 'large',
    skin: ButtonSkin,
    type: 'button' | 'submit' | 'reset',
    value: T,
|};

export default class Button<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        active: false,
        disabled: false,
        loading: false,
        showDropdownIcon: false,
        size: 'large',
        skin: 'secondary',
        type: 'button',
        value: undefined,
    };

    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.preventDefault();
        const onClick = this.props.onClick;

        if (onClick) {
            onClick(this.props.value);
        }
    };

    render() {
        const {
            active,
            activeClassName,
            buttonRef,
            children,
            className,
            disabled,
            icon,
            iconClassName,
            loading,
            onClick,
            showDropdownIcon,
            skin,
            type,
        } = this.props;

        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[skin],
            {
                [buttonStyles.loading]: loading,
                [buttonStyles.active]: active,
                [activeClassName || '']: active && activeClassName,
            },
            className
        );
        const iconClass = classNames(
            buttonStyles.buttonIcon,
            iconClassName
        );

        return (
            <button
                className={buttonClass}
                disabled={loading || disabled}
                onClick={onClick ? this.handleClick : undefined}
                ref={buttonRef}
                type={type}
            >
                {icon &&
                    <Icon className={iconClass} name={icon} />
                }
                {children &&
                    <span className={buttonStyles.text}>{children}</span>
                }
                {showDropdownIcon &&
                    <Icon className={buttonStyles.dropdownIcon} name="su-angle-down" />
                }
                {loading &&
                    <div className={buttonStyles.loader}>
                        <Loader size={LOADER_SIZE} />
                    </div>
                }
            </button>
        );
    }
}
