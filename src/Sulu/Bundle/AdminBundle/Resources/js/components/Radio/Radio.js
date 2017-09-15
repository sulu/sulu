// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import radioStyles from './radio.scss';

type Props = SwitchProps & {
    skin: 'dark' | 'light',
    onChange?: (value?: string | number) => void,
};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
        useLabel: true,
    };

    handleChange = (checked: boolean, value?: string | number) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const {
            name,
            value,
            checked,
            useLabel,
            children,
        } = this.props;
        const radioClass = classNames(
            radioStyles.radio,
            radioStyles[this.props.skin]
        );

        return (
            <Switch
                type="radio"
                name={name}
                value={value}
                checked={checked}
                useLabel={useLabel}
                onChange={this.handleChange}
                className={radioClass}
            >
                {children}
            </Switch>
        );
    }
}
