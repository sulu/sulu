// @flow
import React from 'react';
import Input from '../Input';

type Props = {|
    name?: string,
    placeholder?: string,
    valid: boolean,
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
            valid,
            name,
            placeholder,
            onBlur,
            onChange,
            value,
        } = this.props;

        return (
            <Input
                icon="su-phone"
                onChange={onChange}
                value={value}
                type="tel"
                valid={valid}
                name={name}
                placeholder={placeholder}
                onBlur={onBlur}
                onIconClick={(value && 1 < value.length) ? this.handleIconClick : undefined}
            />
        );
    }
}
