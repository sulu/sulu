// @flow
import React from 'react';
import Icon from '../Icon';
import inputStyles from './input.scss';

type Props = {
    icon?: string,
    type: string,
    value?: string,
    placeholder?: string,
    onChange: (password: string) => void,
};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'text',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (typeof event.target.value === 'string') {
            this.props.onChange(event.target.value);
        }
    };

    render() {
        return (
            <label className={inputStyles.input}>
                {this.props.icon &&
                    <Icon className={inputStyles.icon} name={this.props.icon} />
                }
                <input
                    type={this.props.type}
                    value={this.props.value}
                    placeholder={this.props.placeholder}
                    onChange={this.handleChange} />
            </label>
        );
    }
}
