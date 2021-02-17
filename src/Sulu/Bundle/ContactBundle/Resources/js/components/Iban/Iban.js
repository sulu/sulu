// @flow
import React from 'react';
import {Input} from 'sulu-admin-bundle/components';

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

class Iban extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
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
                icon="su-credit-card"
                id={id}
                name={name}
                onBlur={this.handleBlur}
                onChange={this.handleChange}
                placeholder={placeholder}
                type="text"
                valid={valid}
                value={value}
            />
        );
    }
}

export default Iban;
