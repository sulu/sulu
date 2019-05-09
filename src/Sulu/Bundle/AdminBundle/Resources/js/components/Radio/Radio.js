// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import radioStyles from './radio.scss';

type Props = {|
    ...SwitchProps,
    onChange?: (value?: string | number) => void,
    skin: 'dark' | 'light',
|};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        checked: false,
        disabled: false,
        skin: 'dark',
    };

    handleChange = (checked: boolean, value?: string | number) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const {
            disabled,
            name,
            value,
            checked,
            children,
        } = this.props;

        const radioClass = classNames(
            radioStyles.radio,
            radioStyles[this.props.skin],
            {
                [radioStyles.disabled]: disabled,
            }
        );

        return (
            <Switch
                checked={checked}
                className={radioClass}
                disabled={disabled}
                name={name}
                onChange={this.handleChange}
                type="radio"
                value={value}
            >
                {children}
            </Switch>
        );
    }
}
