// @flow
import React from 'react';
import Switch from '../Switch';
import type {SwitchProps} from '../Switch';
import togglerStyles from './toggler.scss';

type Props = SwitchProps & {
    onChange?: (checked: boolean, value?: string | number) => void,
};

export default class Toggler extends React.PureComponent<Props> {
    static defaultProps = {
        useLabel: true,
    };

    render() {
        const {
            name,
            value,
            checked,
            children,
            onChange,
            useLabel,
        } = this.props;

        return (
            <Switch
                className={togglerStyles.toggler}
                checked={checked}
                value={value}
                name={name}
                onChange={onChange}
                useLabel={useLabel}
            >
                {children}
            </Switch>
        );
    }
}
