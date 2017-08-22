// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import checkboxStyles from './checkbox.scss';

type Props = {
    checked: boolean,
    value: string | true,
    skin: 'dark' | 'light',
    className: string,
    onChange?: (value: string | boolean) => void,
};

export default class Checkbox extends React.PureComponent<Props> {
    input: ElementRef<'input'>;

    static defaultProps = {
        value: true,
        skin: 'dark',
        className: '',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            const value = event.currentTarget.checked ? this.props.value : false;
            this.props.onChange(value);
        }
    };

    handleClick = () => this.input.click();
    handleInputClick = (event: Event) => event.stopPropagation();
    setInput = (input: ElementRef<'input'>) => this.input = input;

    render() {
        const classNames = classnames({
            [checkboxStyles.checkbox]: true,
            [checkboxStyles[this.props.skin]]: true,
            [this.props.className]: !!this.props.className,
        });

        return (
            <span onClick={this.handleClick} className={classNames}>
                <input
                    ref={this.setInput}
                    type="checkbox"
                    checked={this.props.checked}
                    onClick={this.handleInputClick}
                    onChange={this.handleChange} />
                <span>
                    {this.props.checked && <Icon className={checkboxStyles.icon} name="check" />}
                </span>
            </span>
        );
    }
}
