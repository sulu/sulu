// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Icon from '../Icon';
import Loader from '../Loader';
import type {FieldProps} from '../../types';
import inputStyles from './input.scss';

const LOADER_SIZE = 20;

type Props = FieldProps<string> & {
    name?: string,
    icon?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
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
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        const {
            name,
            icon,
            type,
            value,
            loading,
            placeholder,
        } = this.props;

        return (
            <label
                className={inputStyles.input}
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
                    onChange={this.handleChange}
                />
            </label>
        );
    }
}
