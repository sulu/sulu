// @flow
import * as React from 'react';
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

    input: React.ElementRef<'input'>;

    handleChange = () => {
        if (this.props.onChange) {
            this.props.onChange(this.props.value);
        }
    };

    handleButtonClick = () => this.input.click();
    handleInputClick = (event: Event) => event.stopPropagation();
    setInput = (input: React.ElementRef<'input'>) => this.input = input;

    render() {
        return (
            <span onClick={this.handleButtonClick} className={radioStyles.radio}>
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
