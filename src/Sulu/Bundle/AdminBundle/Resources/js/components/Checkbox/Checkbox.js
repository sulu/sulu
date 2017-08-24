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
        const {checked, value, name, onChange, children} = this.props;

        return (
            <Switch
                className={checkboxClass}
                checked={checked}
                value={value}
                name={name}
                icon={checked ? CHECKED_ICON : undefined}
                onChange={onChange}>
                {children}
            </Switch>
        );
    }
}
