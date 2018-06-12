// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {SwitchProps} from './types';
import switchStyles from './switch.scss';

type Props = SwitchProps & {
    className?: string,
    icon?: string,
    type: string,
    active: boolean,
    onChange?: (checked: boolean, value?: string | number) => void,
};

export default class Switch extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'checkbox',
        active: true,
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
            active,
        } = this.props;
        const switchClass = classNames(
            switchStyles.switch,
            {
                [switchStyles.inactive]: !active,
            },
            className
        );

        return (
            <label className={switchStyles.label} onClick={this.handleClick}>
                <span className={switchClass}>
                    <input
                        type={type}
                        name={name}
                        value={value}
                        checked={checked}
                        disabled={!active}
                        onChange={this.handleChange}
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
