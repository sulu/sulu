// @flow
import React from 'react';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import togglerStyles from './toggler.scss';

type Props = {|
    ...SwitchProps,
    onChange?: (checked: boolean, value?: string | number) => void,
|};

export default class Toggler extends React.PureComponent<Props> {
    static defaultProps = {
        checked: false,
        disabled: false,
    };

    render() {
        const {
            disabled,
            name,
            value,
            checked,
            children,
            onChange,
        } = this.props;

        return (
            <Switch
                checked={checked}
                className={togglerStyles.toggler}
                disabled={disabled}
                name={name}
                onChange={onChange}
                value={value}
            >
                {children}
            </Switch>
        );
    }
}
