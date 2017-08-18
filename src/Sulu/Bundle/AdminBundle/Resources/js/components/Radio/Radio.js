// @flow
import React from 'react';
import type {ElementRef} from 'react';
import radioStyles from './radio.scss';

type Props = {
    checked: boolean,
    value: string,
    onChange?: (value: string) => void,
};

export default class Radio extends React.PureComponent<Props> {
    static defaultProps = {
        value: 'on',
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
        return (
            <span onClick={this.handleClick} className={radioStyles.radio}>
                <input
                    ref={this.setInput}
                    type="radio"
                    checked={this.props.checked}
                    onClick={this.handleInputClick}
                    onChange={this.handleChange} />
                <span />
            </span>
        );
    }
}
