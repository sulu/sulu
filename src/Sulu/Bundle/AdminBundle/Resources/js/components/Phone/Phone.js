// @flow
import React from 'react';
import Input from '../Input';

type Props = {|
    id?: string,
    name?: string,
    placeholder?: string,
    valid: boolean,
    disabled?: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string, event: SyntheticEvent<HTMLInputElement>) => void,
|};

export default class Phone extends React.PureComponent<Props> {
    static defaultProps = {
        valid: true,
    };

    handleIconClick = () => {
        const {value} = this.props;
        if (!value) {
            return;
        }

        window.location.assign('tel:' + value);
    };

    render() {
        const {
            id,
            valid,
            disabled,
            name,
            placeholder,
            onBlur,
            onChange,
            value,
        } = this.props;

        return (
            <Input
                disabled={disabled}
                icon="su-phone"
                id={id}
                name={name}
                onBlur={onBlur}
                onChange={onChange}
                onIconClick={(value && value.length > 1) ? this.handleIconClick : undefined}
                placeholder={placeholder}
                type="tel"
                valid={valid}
                value={value}
            />
        );
    }
}
