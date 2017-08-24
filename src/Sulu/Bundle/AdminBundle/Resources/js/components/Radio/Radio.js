// @flow
import React from 'react';
import classNames from 'classnames';
import radioStyles from './radio.scss';

type Props = {
    checked: boolean,
    value: string | number,
    skin: 'dark' | 'light',
    name?: string,
    onChange?: (value: string | number) => void,
    children?: Node,
    className?: string,
};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
        checked: false,
    };

    handleChange = () => {
        if (this.props.onChange) {
            this.props.onChange(this.props.value);
        }
    };

    render() {
        const {
            skin,
            name,
            checked,
            children,
            className,
        } = this.props;
        const radioClass = classNames(
            className,
            radioStyles.radio,
            radioStyles[skin],
        );

        return (
            <label className={radioClass}>
                <span className={radioStyles.customRadioContainer}>
                    <input
                        type="radio"
                        name={name}
                        checked={checked}
                        onChange={this.handleChange} />
                    <span className={radioStyles.customRadio} />
                </span>
                {children &&
                    <span className={radioStyles.labelText}>
                        {children}
                    </span>
                }
            </label>
        );
    }
}
