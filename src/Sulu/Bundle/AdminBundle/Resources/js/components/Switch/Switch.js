// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon/index';
import type {SwitchProps} from './types';
import switchStyles from './switch.scss';

type Props = SwitchProps & {
    className?: string,
    icon?: string,
    type: string,
};

export default class Switch extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'checkbox',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked, this.props.value);
        }
    };

    render() {
        const switchClasses = classNames(
            switchStyles.switch,
            this.props.className
        );

        return (
            <label className={switchStyles.label}>
                <span className={switchClasses}>
                    <input
                        type={this.props.type}
                        name={this.props.name}
                        checked={this.props.checked}
                        onChange={this.handleChange} />
                    <span>
                        {this.props.icon && <Icon name={this.props.icon} />}
                    </span>
                </span>
                {this.props.children && <span>{this.props.children}</span>}
            </label>
        );
    }
}
