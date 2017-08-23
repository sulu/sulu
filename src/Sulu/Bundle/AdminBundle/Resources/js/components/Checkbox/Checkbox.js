// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import checkboxStyles from './checkbox.scss';

type Props = {
    checked: boolean,
    skin?: 'dark' | 'light',
    value?: string | number,
    onChange?: (checked: boolean, value?: string | number) => void,
    className?: string,
};

export default class Checkbox extends React.PureComponent<Props> {
    input: ElementRef<'input'>;

    static defaultProps = {
        skin: 'dark',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked, this.props.value);
        }
    };

    handleClick = () => this.input.click();

    handleInputClick = (event: Event) => event.stopPropagation();

    setInput = (input: ElementRef<'input'>) => this.input = input;

    render() {
        const {
            checked,
            className,
        } = this.props;
        const checkboxClass = classNames(
            className,
            checkboxStyles.checkbox,
            checkboxStyles[this.props.skin],
        );

        return (
            <span onClick={this.handleClick} className={checkboxClass}>
                <input
                    ref={this.setInput}
                    type="checkbox"
                    checked={checked}
                    onClick={this.handleInputClick}
                    onChange={this.handleChange} />
                <span>
                    {checked && <Icon className={checkboxStyles.icon} name="check" />}
                </span>
            </span>
        );
    }
}
