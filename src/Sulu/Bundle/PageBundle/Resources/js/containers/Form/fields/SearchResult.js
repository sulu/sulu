// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import SearchResultComponent from '../../../components/SearchResult';

@observer
class SearchResult extends React.Component<FieldTypeProps<typeof undefined>> {
    render() {
        const {formInspector} = this.props;
        const locale = formInspector.locale ? formInspector.locale.get() : undefined;

        const description = formInspector.getValueByPath('/ext/seo/description');
        const title = formInspector.getValueByPath('/ext/seo/title');
        const url = formInspector.getValueByPath('/url');

        if (title !== undefined && typeof title !== 'string') {
            throw new Error('If "title" is defined it must be a string!');
        }

        if (description !== undefined && typeof description !== 'string') {
            throw new Error('If description is defined it must be a string!');
        }

        if (url !== undefined && typeof url !== 'string') {
            throw new Error('If "url" is defined it must be a string!');
        }

        return (
            <SearchResultComponent
                description={description}
                title={title}
                url={'www.example.org' + (locale ? '/' + locale : '') + (url ? url : '')}
            />
        );
    }
}

export default SearchResult;
