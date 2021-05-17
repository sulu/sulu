// @flow
import React from 'react';
import Switch from '../Switch';
import togglerStyles from './toggler.scss';
import type {SwitchProps} from '../Switch';

type Props<T> = {|
    ...SwitchProps<T>,
    onChange?: (checked: boolean, value?: string | number) => void,
|};

export default class Toggler<T: string | number> extends React.PureComponent<Props<T>> {
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
