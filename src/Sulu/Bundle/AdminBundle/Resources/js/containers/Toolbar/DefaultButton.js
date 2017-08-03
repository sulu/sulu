// @flow
import Icon from '../../components/Icon';
import type {DefaultButtonType} from './types';
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

    render() {
        const {
            icon,
            value,
            onClick,
            disabled,
            isActive,
            hasOptions,
        } = this.props;
        const buttonClasses = classNames({
            [buttonStyles.button]: true,
            [buttonStyles.isActive]: isActive,
        });

        return (
            <div className={buttonStyles.buttonContainer}>
                <button disabled={disabled} className={buttonClasses} onClick={onClick}>
                    <Icon name={icon} className={buttonStyles.icon} />
                    <span className={buttonStyles.value}>{value}</span>
                    {hasOptions &&
                        <Icon name={ICON_ARROW_DOWN} className={buttonStyles.dropdownIcon} />
                    }
                </button>
            </div>
        );
    }
}
