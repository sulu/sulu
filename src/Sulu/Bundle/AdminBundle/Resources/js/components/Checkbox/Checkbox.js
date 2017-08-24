// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import checkboxStyles from './checkbox.scss';

type Props = SwitchProps & {
    skin: 'dark' | 'light',
};

const CHECKED_ICON = 'check';

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
    };

    render() {
        const checkboxClass = classNames(
            checkboxStyles.checkbox,
            checkboxStyles[this.props.skin]
        );

        return (
            <Switch
                className={checkboxClass}
                checked={this.props.checked}
                value={this.props.value}
                name={this.props.name}
                icon={this.props.checked ? CHECKED_ICON : undefined}
                onChange={this.props.onChange}>
                {this.props.children}
            </Switch>
        );
    }
}
