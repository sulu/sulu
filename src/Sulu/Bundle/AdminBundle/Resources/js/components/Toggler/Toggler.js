// @flow
import React from 'react';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import togglerStyles from './toggler.scss';

type Props = SwitchProps & {
    onChange?: (checked: boolean, value?: string | number) => void,
};

export default class Toggler extends React.PureComponent<Props> {
    render() {
        const {checked, value, name, onChange, children} = this.props;
        return (
            <Switch
                className={togglerStyles.toggler}
                checked={checked}
                value={value}
                name={name}
                onChange={onChange}
            >
                {children}
            </Switch>
        );
    }
}
