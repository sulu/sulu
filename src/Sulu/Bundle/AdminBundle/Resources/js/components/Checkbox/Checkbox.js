// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import checkboxStyles from './checkbox.scss';

type Props = {
    checked: boolean,
    skin: 'dark' | 'light',
    name?: string,
    value?: string | number,
    onChange?: (checked: boolean, value?: string | number) => void,
    className?: string,
};

export default class Checkbox extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'dark',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked, this.props.value);
        }
    };

    render() {
        const {
            name,
            checked,
            className,
        } = this.props;
        const checkboxClass = classNames(
            className,
            checkboxStyles.checkbox,
            checkboxStyles[this.props.skin],
        );

        return (
            <span className={checkboxClass}>
                <input
                    type="checkbox"
                    name={name}
                    checked={checked}
                    onChange={this.handleChange} />
                <span>
                    {checked &&
                        <Icon className={checkboxStyles.icon} name="check" />
                    }
                </span>
            </span>
        );
    }
}
