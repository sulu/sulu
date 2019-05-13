// @flow
import React from 'react';
import {Email as EmailComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Field from './Field';

type Props = {|
    email: ?string,
    index: number,
    onBlur: () => void,
    onEmailChange: (index: number, email: ?string) => void,
    onRemove: (index: number) => void,
|};

export default class Email extends React.Component<Props> {
    handleEmailChange = (email: ?string) => {
        const {index, onEmailChange} = this.props;

        onEmailChange(index, email);
    };

    render() {
        const {email, index, onBlur, onRemove} = this.props;

        return (
            <Field index={index} label={translate('sulu_contact.email')} onRemove={onRemove}>
                <EmailComponent onBlur={onBlur} onChange={this.handleEmailChange} value={email} />
            </Field>
        );
    }
}
