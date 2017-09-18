// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Icon from '../Icon';
import inputStyles from './input.scss';

type Props = {
    name?: string,
    icon?: string,
    type: string,
    value: ?string,
    placeholder?: string,
    onChange: (value: string) => void,
    inputRef?: (ref: ElementRef<'label'>) => void,
    onFocus?: () => void,
};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'text',
    };

    setRef = (ref: ElementRef<'label'>) => {
        if (this.props.inputRef) {
            this.props.inputRef(ref);
        }
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        if (this.props.onChange) {
            this.props.onChange(event.currentTarget.value);
        }
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
            <label
                className={inputStyles.input}
                ref={this.setRef}
            >
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
