// @flow
import React from 'react';
import {Url} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    onWebsiteChange: (index: number, website: ?string) => void,
    type: number,
    website: ?string,
|};

export default class Website extends React.Component<Props> {
    static types: FormFieldTypes;

    handleUrlChange = (url: ?string) => {
        const {index, onWebsiteChange} = this.props;

        onWebsiteChange(index, url);
    };

    render() {
        const {index, onBlur, onRemove, onTypeChange, type, website} = this.props;

        return (
            <Field
                index={index}
                label={translate('sulu_contact.website')}
                onRemove={onRemove}
                onTypeChange={onTypeChange}
                type={type}
                types={Website.types}
            >
                <Url onBlur={onBlur} onChange={this.handleUrlChange} value={website} />
            </Field>
        );
    }
}
