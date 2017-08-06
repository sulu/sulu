// @flow
import ButtonSizes from './buttonSizes';
import type {DefaultButtonType} from './types';
import Icon from '../../components/Icon';
import React from 'react';
import buttonStyles from './button.scss';
import classNames from 'classnames';

const ICON_ARROW_DOWN = 'chevron-down';

export default class DefaultButton extends React.PureComponent {
    props: DefaultButtonType;

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
        const buttonSizeClass = size ? ButtonSizes.getClassName(size) : null;
        const buttonClasses = classNames({
            [buttonStyles.button]: true,
            [buttonStyles.isActive]: isActive,
            [buttonStyles[buttonSizeClass]]: buttonSizeClass,
        });

        return (
            <button disabled={disabled} className={buttonClasses} onClick={this.handleOnClick}>
                {icon &&
                    <Icon name={icon} className={buttonStyles.icon} />
                }
                <span className={buttonStyles.label}>{value}</span>
                {hasOptions &&
                    <Icon name={ICON_ARROW_DOWN} className={buttonStyles.dropdownIcon} />
                }
            </button>
        );
    }
}
