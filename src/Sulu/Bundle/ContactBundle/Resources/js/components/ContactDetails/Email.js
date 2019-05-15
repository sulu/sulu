// @flow
import React from 'react';
import {Email as EmailComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import Field from './Field';

type Props = {|
    email: ?string,
    index: number,
    onBlur: () => void,
    onEmailChange: (index: number, email: ?string) => void,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    type: number,
|};

export default class Email extends React.Component<Props> {
    static types: FormFieldTypes;

    handleEmailChange = (email: ?string) => {
        const {index, onEmailChange} = this.props;

        onEmailChange(index, email);
    };

    render() {
        const {email, index, onBlur, onRemove, onTypeChange, type} = this.props;

        return (
            <Field
                index={index}
                label={translate('sulu_contact.email')}
                onRemove={onRemove}
                onTypeChange={onTypeChange}
                type={type}
                types={Email.types}
            >
                <EmailComponent onBlur={onBlur} onChange={this.handleEmailChange} value={email} />
            </Field>
        );
    }
}
