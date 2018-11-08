// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import checkboxStyles from './checkbox.scss';

type Props = {|
    ...SwitchProps,
    skin: 'dark' | 'light',
    className?: string,
    disabled: boolean,
    onChange?: (checked: boolean, value?: string | number) => void,
|};

const CHECKED_ICON = 'su-check';

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        skin: 'dark',
    };

    render() {
        const {
            skin,
            name,
            value,
            checked,
            onChange,
            children,
            className,
            disabled,
        } = this.props;
        const checkboxClass = classNames(
            checkboxStyles.checkbox,
            checkboxStyles[skin],
            className
        );

        return (
            <Switch
                checked={checked}
                className={checkboxClass}
                disabled={disabled}
                icon={checked ? CHECKED_ICON : undefined}
                name={name}
                onChange={onChange}
                value={value}
            >
                {children}
            </Switch>
        );
    }
}
