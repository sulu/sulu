// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle';
import SearchResultComponent from '../../../components/SearchResult';

export default class SearchResult extends React.Component<FieldTypeProps<typeof undefined>> {
    render() {
        const {formInspector} = this.props;
        const locale = formInspector.locale ? formInspector.locale.get() : undefined;

        const description = formInspector.getValueByPath('/description');
        const title = formInspector.getValueByPath('/title');
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
