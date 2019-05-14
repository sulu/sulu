// @flow
import React from 'react';
import {Phone as PhoneComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import Field from './Field';

type Props = {|
    fax: ?string,
    index: number,
    onBlur: () => void,
    onFaxChange: (index: number, fax: ?string) => void,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    type: number,
|};

export default class Fax extends React.Component<Props> {
    static types: FormFieldTypes;

    handleFaxChange = (fax: ?string) => {
        const {index, onFaxChange} = this.props;

        onFaxChange(index, fax);
    };

    render() {
        const {fax, index, onBlur, onRemove, onTypeChange, type} = this.props;

        return (
            <Field
                index={index}
                label={translate('sulu_contact.fax')}
                onRemove={onRemove}
                onTypeChange={onTypeChange}
                type={type}
                types={Fax.types}
            >
                <PhoneComponent onBlur={onBlur} onChange={this.handleFaxChange} value={fax} />
            </Field>
        );
    }
}
