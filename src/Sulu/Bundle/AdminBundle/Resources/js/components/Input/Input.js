// @flow
import React from 'react';
import Icon from '../Icon';
import inputStyles from './input.scss';

type Props = {
    name?: string,
    icon?: string,
    type: string,
    value?: string,
    placeholder?: string,
    onChange: (value: string) => void,
};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'text',
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        const {
            name,
            icon,
            type,
            value,
            placeholder,
        } = this.props;

        return (
            <label className={inputStyles.input}>
                {icon &&
                    <Icon className={inputStyles.icon} name={icon} />
                }
                <input
                    name={name}
                    type={type}
                    value={value || ''}
                    placeholder={placeholder}
                    onChange={this.handleChange}
                />
            </label>
        );
    }
}
