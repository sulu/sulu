// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {FormInspector, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SearchResult from '../../fields/SearchResult';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    FormStore: jest.fn(function(resourceStore) {
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.locale = observableOptions.locale;
    }),
}));

test('Pass correct fields to SearchResult component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
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
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            formInspector={formInspector}
            schemaOptions={{}}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(searchResult.prop('description')).toEqual('SEO description');
    expect(searchResult.prop('title')).toEqual('SEO title');
    expect(searchResult.prop('url')).toEqual('www.example.org/url');
});

test('Pass correct fields to SearchResult component', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
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
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            formInspector={formInspector}
            schemaOptions={{}}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(searchResult.prop('description')).toEqual('SEO description');
    expect(searchResult.prop('title')).toEqual('SEO title');
    expect(searchResult.prop('url')).toEqual('www.example.org/en/url');
});
