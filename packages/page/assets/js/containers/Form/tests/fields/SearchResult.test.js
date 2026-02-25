// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SearchResult from '../../fields/SearchResult';

test('Pass correct fields to SearchResult component', () => {
    const formInspector: any = {
        getValueByPath: jest.fn((path) => {
            switch (path) {
                case '/seo/description':
                    return 'SEO description';
                case '/seo/title':
                    return 'SEO title';
                case '/url':
                    return '/url';
            }
        }),
        locale: undefined,
    };
    render(
        <SearchResult
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(screen.getByText('SEO description')).toBeInTheDocument();
    expect(screen.getByText('SEO title')).toBeInTheDocument();
    expect(screen.getByText('www.example.org/url')).toBeInTheDocument();
});

test('Pass correct fields to SearchResult component with PageTreeRoute', () => {
    const formInspector: any = {
        getValueByPath: jest.fn((path) => {
            switch (path) {
                case '/seo/description':
                    return 'SEO description';
                case '/seo/title':
                    return 'SEO title';
                case '/url':
                    return {
                        page: {
                            uuid: '019a9d17-6a7d-7d56-acc0-0068d1cd4040',
                            path: '/page',
                        },
                        suffix: '/article',
                    };
            }
        }),
        locale: undefined,
    };
    render(
        <SearchResult
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(screen.getByText('SEO description')).toBeInTheDocument();
    expect(screen.getByText('SEO title')).toBeInTheDocument();
    expect(screen.getByText('www.example.org/page/article')).toBeInTheDocument();
});

test('Pass correct fields to SearchResult component with locale prefix', () => {
    const formInspector: any = {
        getValueByPath: jest.fn((path) => {
            switch (path) {
                case '/seo/description':
                    return 'SEO description';
                case '/seo/title':
                    return 'SEO title';
                case '/url':
                    return '/url';
            }
        }),
        locale: observable.box('en'),
    };
    render(
        <SearchResult
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(screen.getByText('SEO description')).toBeInTheDocument();
    expect(screen.getByText('SEO title')).toBeInTheDocument();
    expect(screen.getByText('www.example.org/en/url')).toBeInTheDocument();
});
