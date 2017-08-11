// @flow
import * as React from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import checkboxStyles from './checkbox.scss';

type Props = {
    checked: boolean,
    skin: 'dark' | 'light',
    className: string,
    onChange?: (checked: boolean) => void,
};

export default class Checkbox extends React.PureComponent<Props> {
    input: React.ElementRef<'input'>;

    static defaultProps = {
        skin: 'dark',
        className: '',
    };

    handleChange = (event: Event) => {
        if (this.props.onChange && typeof event.target.checked === 'boolean') {
            this.props.onChange(event.target.checked);
        }
    };

    handleButtonClick = () => this.input.click();
    handleInputClick = (event: Event) => event.stopPropagation();
    setInput = (input: React.ElementRef<'input'>) => this.input = input;

    render() {
        const classNames = classnames({
            [checkboxStyles.checkbox]: true,
            [checkboxStyles.light]: this.props.skin === 'light',
            [this.props.className]: !!this.props.className,
        });

        return (
            <span onClick={this.handleButtonClick} className={classNames}>
                <input
                    ref={this.setInput}
                    type="checkbox"
                    checked={this.props.checked}
                    onClick={this.handleInputClick}
                    onChange={this.handleChange} />
                <span>
                    {this.props.checked ? <Icon className={checkboxStyles.icon} name="check" /> : ''}
                </span>
            </span>
        );
    }
}
