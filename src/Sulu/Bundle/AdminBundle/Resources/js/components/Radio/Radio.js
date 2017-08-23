// @flow
import React from 'react';
import type {ElementRef, Node} from 'react';
import classNames from 'classnames';
import radioStyles from './radio.scss';

type Props = {
    name: string,
    checked: boolean,
    value: string | number,
    skin?: 'dark' | 'light',
    onChange?: (value: string | number) => void,
    children?: Node,
    className?: string,
};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
        checked: false,
    };

    input: ElementRef<'input'>;

    handleChange = () => {
        if (this.props.onChange) {
            this.props.onChange(this.props.value);
        }
    };

    handleClick = () => this.input.click();

    handleInputClick = (event: Event) => event.stopPropagation();

    setInput = (input: ElementRef<'input'>) => this.input = input;

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
                <span
                    className={radioStyles.customRadioContainer}
                    onClick={this.handleClick}>
                    <input
                        ref={this.setInput}
                        type="radio"
                        name={name}
                        checked={checked}
                        onClick={this.handleInputClick}
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
