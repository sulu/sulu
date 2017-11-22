// @flow
import React from 'react';
import textAreaStyles from './textArea.scss';

type Props = {
    name?: string,
    value?: string,
    placeholder?: string,
    onChange: (value: string) => void,
};

export default class TextArea extends React.PureComponent<Props> {
    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        const {
            name,
            value,
            placeholder,
        } = this.props;

        return (
            <textarea
                name={name}
                className={textAreaStyles.textArea}
                value={value || ''}
                placeholder={placeholder}
                onChange={this.handleChange}
            />
        );
    }
}
