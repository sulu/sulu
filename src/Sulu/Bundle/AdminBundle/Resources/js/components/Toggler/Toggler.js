// @flow
import React from 'react';
import type {CheckboxProps} from '../Checkbox/types';
import {GenericCheckbox} from '../Checkbox';
import togglerStyles from './toggler.scss';

export default class Toggler extends React.PureComponent<CheckboxProps> {
    render() {
        return (
            <GenericCheckbox
                className={togglerStyles.toggler}
                checked={this.props.checked}
                value={this.props.value}
                name={this.props.name}
                onChange={this.props.onChange}>
                {this.props.children}
            </GenericCheckbox>
        );
    }
}
