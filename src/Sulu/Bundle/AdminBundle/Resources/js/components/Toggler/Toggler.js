// @flow
import React from 'react';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import togglerStyles from './toggler.scss';

export default class Toggler extends React.PureComponent<SwitchProps> {
    render() {
        return (
            <Switch
                className={togglerStyles.toggler}
                checked={this.props.checked}
                value={this.props.value}
                name={this.props.name}
                onChange={this.props.onChange}>
                {this.props.children}
            </Switch>
        );
    }
}
