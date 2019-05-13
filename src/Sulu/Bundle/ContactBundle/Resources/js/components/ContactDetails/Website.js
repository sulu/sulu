// @flow
import React from 'react';
import {Url} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onRemove: (index: number) => void,
    onWebsiteChange: (index: number, website: ?string) => void,
    website: ?string,
|};

export default class Website extends React.Component<Props> {
    handleUrlChange = (url: ?string) => {
        const {index, onWebsiteChange} = this.props;

        onWebsiteChange(index, url);
    };

    render() {
        const {index, onBlur, onRemove, website} = this.props;

        return (
            <Field index={index} label={translate('sulu_contact.website')} onRemove={onRemove}>
                <Url onBlur={onBlur} onChange={this.handleUrlChange} value={website} />
            </Field>
        );
    }
}
