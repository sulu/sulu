// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import inputStyles from './input.scss';

const LOADER_SIZE = 20;

type Props = {
    name?: string,
    icon?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
    inputRef?: (ref: ?ElementRef<'label'>) => void,
    valid: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'text',
        valid: true,
    };

    setRef = (ref: ?ElementRef<'label'>) => {
        if (this.props.inputRef) {
            this.props.inputRef(ref);
        }
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined);
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {
            valid,
            icon,
            loading,
            name,
            placeholder,
            type,
            value,
        } = this.props;

        const labelClass = classNames(
            inputStyles.input,
            {
                [inputStyles.error]: !valid,
            }
        );

        return (
            <label
                className={labelClass}
                ref={this.setRef}
            >
                {!loading && icon &&
                    <div className={inputStyles.prependedContainer}>
                        <Icon className={inputStyles.icon} name={icon} />
                    </div>
                }
                {loading &&
                    <div className={inputStyles.prependedContainer}>
                        <Loader size={LOADER_SIZE} />
                    </div>
                }
                <input
                    name={name}
                    type={type}
                    value={value || ''}
                    placeholder={placeholder}
                    onBlur={this.handleBlur}
                    onChange={this.handleChange}
                />
            </label>
        );
    }
}
