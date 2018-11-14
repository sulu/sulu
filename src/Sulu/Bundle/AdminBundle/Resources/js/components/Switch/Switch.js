// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {SwitchProps} from './types';
import switchStyles from './switch.scss';

type Props = {|
    ...SwitchProps,
    className?: string,
    icon?: string,
    type: string,
    onChange?: (checked: boolean, value?: string | number) => void,
|};

export default class Switch extends React.PureComponent<Props> {
    static defaultProps = {
        checked: false,
        disabled: false,
        type: 'checkbox',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked, this.props.value);
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
