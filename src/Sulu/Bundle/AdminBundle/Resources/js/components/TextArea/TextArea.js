// @flow
import React from 'react';
import classNames from 'classnames';
import type {FieldTypeProps} from '../../types';
import textAreaStyles from './textArea.scss';

type Props = FieldTypeProps<string> & {
    name?: string,
    placeholder?: string,
};

export default class TextArea extends React.PureComponent<Props> {
    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        const {
            error,
            name,
            onFinish,
            placeholder,
            value,
        } = this.props;

        const textareaClass = classNames(
            textAreaStyles.textArea,
            {
                [textAreaStyles.error]: error,
            }
        );

        return (
            <textarea
                name={name}
                className={textareaClass}
                value={value || ''}
                placeholder={placeholder}
                onBlur={onFinish}
                onChange={this.handleChange}
            />
        );
    }
}
