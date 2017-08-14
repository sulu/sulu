// @flow
import React from 'react';
import type {ElementRef} from 'react';
import togglerStyles from './toggler.scss';

type Props = {
    checked: boolean,
    onChange?: (checked: boolean) => void,
}

export default class Toggler extends React.PureComponent<Props> {
    input: ElementRef<'input'>;

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.checked);
        }
    };

    handleClick = () => this.input.click();
    handleInputClick = (event: Event) => event.stopPropagation();
    setInput = (input: ElementRef<'input'>) => this.input = input;

    render() {
        return (
            <span onClick={this.handleClick} className={togglerStyles.toggler}>
                <input
                    ref={this.setInput}
                    type="checkbox"
                    checked={this.props.checked}
                    onClick={this.handleInputClick}
                    onChange={this.handleChange} />
                <span />
            </span>
        );
    }
}
