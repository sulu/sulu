// @flow
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SearchResult from '../../fields/SearchResult';
import SearchResultComponent from '../../../../components/SearchResult';

jest.mock('../../../../components/SearchResult', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct fields to SearchResult component without locale', () => {
    const formInspector = ({
        getValueByPath: jest.fn((path) => {
            switch (path) {
                case '/ext/seo/description':
                    return 'SEO description';
                case '/ext/seo/title':
                    return 'SEO title';
                case '/url':
                    return '/url';
                default:
                    return undefined;
            }
        }),
        locale: undefined,
    }: any);

    render(<SearchResult {...fieldTypeDefaultProps} formInspector={formInspector} />);

    const [searchResultProps] = (SearchResultComponent: any).mock.calls[0];
    expect(searchResultProps.description).toEqual('SEO description');
    expect(searchResultProps.title).toEqual('SEO title');
    expect(searchResultProps.url).toEqual('www.example.org/url');
});

test('Pass correct fields to SearchResult component with locale', () => {
    const formInspector = ({
        getValueByPath: jest.fn((path) => {
            switch (path) {
                case '/ext/seo/description':
                    return 'SEO description';
                case '/ext/seo/title':
                    return 'SEO title';
                case '/url':
                    return '/url';
                default:
                    return undefined;
            }
        }),
        locale: observable.box('en'),
    }: any);

    render(<SearchResult {...fieldTypeDefaultProps} formInspector={formInspector} />);

    const [searchResultProps] = (SearchResultComponent: any).mock.calls[0];
    expect(searchResultProps.description).toEqual('SEO description');
    expect(searchResultProps.title).toEqual('SEO title');
    expect(searchResultProps.url).toEqual('www.example.org/en/url');
});
