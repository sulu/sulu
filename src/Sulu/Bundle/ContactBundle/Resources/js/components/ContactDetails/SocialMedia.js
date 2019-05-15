// @flow
import React from 'react';
import {Input} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    onUsernameChange: (index: number, fax: ?string) => void,
    type: number,
    username: ?string,
|};

export default class SocialMedia extends React.Component<Props> {
    static types: FormFieldTypes;

    handleInputChange = (username: ?string) => {
        const {index, onUsernameChange} = this.props;

        onUsernameChange(index, username);
    };

    render() {
        const {index, onBlur, onRemove, onTypeChange, type, username} = this.props;

        return (
            <Field
                index={index}
                label={translate('sulu_contact.social_media')}
                onRemove={onRemove}
                onTypeChange={onTypeChange}
                type={type}
                types={SocialMedia.types}
            >
                <Input icon="su-user" onBlur={onBlur} onChange={this.handleInputChange} value={username} />
            </Field>
        );
    }
}
