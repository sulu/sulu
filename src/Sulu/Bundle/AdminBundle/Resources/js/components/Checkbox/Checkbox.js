// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch/types';
import checkboxStyles from './checkbox.scss';

type Props = SwitchProps & {
    skin: 'dark' | 'light',
    className?: string,
};

const CHECKED_ICON = 'check';

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
    };

    render() {
        const {checked, value, name, onChange, children, className, skin} = this.props;
        const checkboxClass = classNames(
            checkboxStyles.checkbox,
            checkboxStyles[skin],
            className,
        );

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
