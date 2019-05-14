// @flow
import React from 'react';
import {Phone as PhoneComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onPhoneChange: (index: number, phone: ?string) => void,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    phone: ?string,
    type: number,
|};

export default class Phone extends React.Component<Props> {
    static types: FormFieldTypes;

    handlePhoneChange = (phone: ?string) => {
        const {index, onPhoneChange} = this.props;

        onPhoneChange(index, phone);
    };

    render() {
        const {index, onBlur, onRemove, onTypeChange, phone, type} = this.props;

        return (
            <Field
                index={index}
                label={translate('sulu_contact.phone')}
                onRemove={onRemove}
                onTypeChange={onTypeChange}
                type={type}
                types={Phone.types}
            >
                <PhoneComponent onBlur={onBlur} onChange={this.handlePhoneChange} value={phone} />
            </Field>
        );
    }
}
