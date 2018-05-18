// @flow
import React from 'react';
import classNames from 'classnames';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import checkboxStyles from './checkbox.scss';

type Props = SwitchProps & {
    skin: 'dark' | 'light',
    className?: string,
    active: boolean,
    onChange?: (checked: boolean, value?: string | number) => void,
};

const CHECKED_ICON = 'su-check';

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        active: true,
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
            active,
        } = this.props;
        const checkboxClass = classNames(
            checkboxStyles.checkbox,
            checkboxStyles[skin],
            className
        );

        return (
            <Switch
                className={checkboxClass}
                checked={checked}
                value={value}
                name={name}
                icon={checked ? CHECKED_ICON : undefined}
                onChange={onChange}
                active={active}
            >
                {children}
            </Switch>
        );
    }
}
