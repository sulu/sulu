// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Input from '../Input';
import type {InputProps} from '../Input';

type Props = {|
    alignment: 'left' | 'center' | 'right',
    collapsed?: boolean,
    name?: string,
    icon?: string,
    loading?: boolean,
    placeholder?: string,
    labelRef?: (ref: ?ElementRef<'label'>) => void,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    valid: boolean,
    value: ?number,
    onBlur?: () => void,
    onChange: (value: ?number, event: SyntheticEvent<HTMLInputElement>) => void,
    onIconClick?: () => void,
    iconStyle?: Object,
    iconClassName?: string,
    min?: ?number,
    max?: ?number,
    skin?: 'default' | 'dark',
    step?: ?number,
|};

export default class Number extends React.PureComponent<Props> {
    static defaultProps = {
        alignment: 'left',
        valid: true,
    };

    handleChange = (value: ?string, event: SyntheticEvent<HTMLInputElement>) => {
        let number = undefined;

        if (value) {
            number = parseFloat(value);

            if (isNaN(number)) {
                number = undefined;
            }
        }

        this.props.onChange(number, event);
    };

    render() {
        const inputProps: InputProps<number> = {
            alignment: this.props.alignment,
            collapsed: this.props.collapsed,
            name: this.props.name,
            icon: this.props.icon,
            loading: this.props.loading,
            placeholder: this.props.placeholder,
            labelRef: this.props.labelRef,
            inputRef: this.props.inputRef,
            valid: this.props.valid,
            value: this.props.value,
            onBlur: this.props.onBlur,
            onIconClick: this.props.onIconClick,
            iconStyle: this.props.iconStyle,
            iconClassName: this.props.iconClassName,
            onChange: this.handleChange,
            min: this.props.min,
            max: this.props.max,
            step: this.props.step,
            skin: this.props.skin,
            type: 'number',
        };

        return <Input {...inputProps} />;
    }
}
