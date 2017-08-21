// @flow
import React from 'react';
import type {ElementRef, Node} from 'react';
import radioStyles from './radio.scss';

type Props = {
    checked: boolean,
    value: string,
    onChange?: (value: string) => void,
    children: Node,
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
            <label className={radioStyles.radio}>
                <span onClick={this.handleClick}>
                    <input
                        ref={this.setInput}
                        type="radio"
                        checked={this.props.checked}
                        onClick={this.handleInputClick}
                        onChange={this.handleChange} />
                    <span />
                </span>
                {this.renderChildren()}
            </label>
        );
    }

    renderChildren() {
        if (this.props.children) {
            return <span>{this.props.children}</span>;
        }

        return null;
    }
}
