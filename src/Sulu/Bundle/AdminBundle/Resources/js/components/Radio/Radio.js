// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import radioStyles from './radio.scss';

type Props = SwitchProps & {
    skin: 'dark' | 'light',
    onChange?: (value?: string | number) => void,
};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
    };

    handleChange = (checked: boolean, value?: string | number) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const radioClass = classNames(
            radioStyles.radio,
            radioStyles[this.props.skin],
        );
        const {checked, value, name, children} = this.props;

        return (
            <Switch
                className={radioClass}
                checked={checked}
                value={value}
                name={name}
                onChange={this.handleChange}
                type="radio">
                {children}
            </Switch>
        );
    }
}
