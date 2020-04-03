// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {SwitchProps} from './types';
import switchStyles from './switch.scss';

type Props<T> = {|
    ...SwitchProps<T>,
    className?: string,
    icon?: string,
    onChange?: (checked: boolean, value?: T) => void,
    type: string,
|};

export default class Switch<T: string | number> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        checked: false,
        disabled: false,
        type: 'checkbox',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        const {onChange, value} = this.props;
        if (onChange) {
            onChange(event.currentTarget.checked, value);
        }
    };

    handleClick = (event: SyntheticEvent<HTMLInputElement>) => {
        event.stopPropagation();
    };

    render() {
        const {
            icon,
            type,
            name,
            value,
            checked,
            children,
            className,
            disabled,
        } = this.props;
        const labelClass = classNames(
            switchStyles.label,
            {
                [switchStyles.disabled]: disabled,
            }
        );
        const switchClass = classNames(
            switchStyles.switch,
            {
                [switchStyles.disabled]: disabled,
            },
            className
        );

        return (
            <label className={labelClass} onClick={this.handleClick}>
                <span className={switchClass}>
                    <input
                        checked={checked}
                        disabled={disabled}
                        name={name}
                        onChange={this.handleChange}
                        type={type}
                        value={value}
                    />
                    <span>
                        {icon &&
                            <Icon name={icon} />
                        }
                    </span>
                </span>
                {children &&
                    <div>{children}</div>
                }
            </label>
        );
    }
}
