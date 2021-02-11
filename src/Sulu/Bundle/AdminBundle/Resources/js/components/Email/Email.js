// @flow
import React from 'react';
import Input from '../Input';

type Props = {|
    disabled: boolean,
    id?: string,
    name?: string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    placeholder?: string,
    valid: boolean,
    value: ?string,
|};

class Email extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
    };

    handleIconClick = () => {
        const {value} = this.props;
        if (!value) {
            return;
        }

        window.location.assign('mailto:' + value);
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    handleChange = (value: ?string) => {
        const {onChange} = this.props;

        onChange(value);
    };

    render() {
        const {
            id,
            valid,
            disabled,
            name,
            placeholder,
            value,
        } = this.props;

        return (
            <Input
                disabled={disabled}
                icon="su-envelope"
                id={id}
                name={name}
                onBlur={this.handleBlur}
                onChange={this.handleChange}
                onIconClick={(value && value.length > 1 && valid) ? this.handleIconClick : undefined}
                placeholder={placeholder}
                type="email"
                valid={valid}
                value={value}
            />
        );
    }
}

export default Email;
