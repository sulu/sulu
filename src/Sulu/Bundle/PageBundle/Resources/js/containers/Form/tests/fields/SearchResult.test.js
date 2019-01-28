// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SearchResult from '../../fields/SearchResult';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    ResourceFormStore: jest.fn(function(resourceStore) {
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.locale = observableOptions.locale;
    }),
}));

test('Pass correct fields to SearchResult component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/description':
                return 'SEO description';
            case '/title':
                return 'SEO title';
            case '/url':
                return '/url';
        }
    });

    const searchResult = shallow(
        <SearchResult
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(searchResult.prop('description')).toEqual('SEO description');
    expect(searchResult.prop('title')).toEqual('SEO title');
    expect(searchResult.prop('url')).toEqual('www.example.org/url');
});

test('Pass correct fields to SearchResult component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/description':
                return 'SEO description';
            case '/title':
                return 'SEO title';
            case '/url':
                return '/url';
        }
    });

    const searchResult = shallow(
        <SearchResult
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(searchResult.prop('description')).toEqual('SEO description');
    expect(searchResult.prop('title')).toEqual('SEO title');
    expect(searchResult.prop('url')).toEqual('www.example.org/en/url');
});
