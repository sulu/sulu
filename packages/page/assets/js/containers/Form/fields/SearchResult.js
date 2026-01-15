// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SearchResultComponent from '../../../components/SearchResult';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

@observer
class SearchResult extends React.Component<FieldTypeProps<typeof undefined>> {
    extractUrl(url: any): string {
        if (typeof url === 'string') {
            return url;
        }

        if (url === undefined) {
            return '';
        }

        if (typeof url === 'object') {
            return this.extractUrlFromPageTreeRoute(url);
        }

        throw new Error('If "url" is defined it must be a string or a object following page tree structure!');
    }

    extractUrlFromPageTreeRoute(url: Object): string {
        let urlPath = '';

        if (typeof url.page === 'object'
            && typeof url.page.path === 'string'
        ) {
            urlPath += url.page.path;
        }

        if (typeof url.suffix === 'string') {
            urlPath += url.suffix;
        }

        return urlPath;
    }

    render() {
        const {formInspector} = this.props;
        const locale = formInspector.locale ? formInspector.locale.get() : undefined;

        const description = formInspector.getValueByPath('/seo/description');
        const title = formInspector.getValueByPath('/seo/title');
        const url = this.extractUrl(formInspector.getValueByPath('/url'));

        if (title !== undefined && typeof title !== 'string') {
            throw new Error('If "title" is defined it must be a string!');
        }

        if (description !== undefined && typeof description !== 'string') {
            throw new Error('If description is defined it must be a string!');
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
