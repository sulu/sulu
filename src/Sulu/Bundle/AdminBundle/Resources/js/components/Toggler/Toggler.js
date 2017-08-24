// @flow
import React from 'react';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import togglerStyles from './toggler.scss';

export default class Toggler extends React.PureComponent<SwitchProps> {
    render() {
        const {checked, value, name, onChange, children} = this.props;
        return (
            <Switch
                className={togglerStyles.toggler}
                checked={checked}
                value={value}
                name={name}
                onChange={onChange}>
                {children}
            </Switch>
        );
    }
}
