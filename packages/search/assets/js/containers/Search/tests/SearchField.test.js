// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import SearchField from '../SearchField';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render without selected searchResource', () => {
    expect(render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            resourceKey={undefined}
            searchResources={undefined}
        />
    )).toMatchSnapshot();
});

test('Render with selected and query', () => {
    const searchResources = {
        contact: {
            icon: 'su-test',
            resourceKey: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
        page: {
            icon: 'su-test',
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    expect(render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey="page"
            searchResources={searchResources}
        />
    )).toMatchSnapshot();
});

test('Call callback when searchResource changes', () => {
    const searchResourceChangeSpy = jest.fn();

    const searchResources = {
        contact: {
            icon: 'su-test',
            resourceKey: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
        page: {
            icon: 'su-test',
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchField = mount(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            onSearchResourceChange={searchResourceChangeSpy}
            resourceKey="page"
            searchResources={searchResources}
        />
    );

    expect(searchField.find('ArrowMenu').prop('open')).toEqual(false);
    searchField.find('button.searchResourceButton').simulate('click');
    expect(searchField.find('ArrowMenu').prop('open')).toEqual(true);
    searchField.find('Item[value="contact"] button').simulate('click');
    expect(searchField.find('ArrowMenu').prop('open')).toEqual(false);

    expect(searchResourceChangeSpy).toBeCalledWith('contact');
});

test('Call callback when query changes', () => {
    const queryChangeSpy = jest.fn();

    const searchField = mount(
        <SearchField
            onQueryChange={queryChangeSpy}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    searchField.find('input.input').prop('onChange')({currentTarget: {value: 'test'}});

    expect(queryChangeSpy).toBeCalledWith('test');
});

test('Call search with query when enter is pressed', () => {
    const searchSpy = jest.fn();

    const searchField = mount(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    searchField.find('input.input').prop('onKeyPress')({key: 'Enter'});

    expect(searchSpy).toBeCalledWith();
});

test('Do not call search when other key than enter is pressed', () => {
    const searchSpy = jest.fn();

    const searchField = mount(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    searchField.find('input.input').prop('onKeyPress')({key: 'a'});

    expect(searchSpy).not.toBeCalledWith();
});

test('Call search with query when search icon is clicked', () => {
    const searchSpy = jest.fn();

    const searchField = mount(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    searchField.find('Icon[name="su-search"]').prop('onClick')();

    expect(searchSpy).toBeCalledWith();
});

test('Remove query when clear icon is clicked', () => {
    const searchSpy = jest.fn();
    const queryChangeSpy = jest.fn();

    const searchField = mount(
        <SearchField
            onQueryChange={queryChangeSpy}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    searchField.find('Icon[name="su-times"]').prop('onClick')();

    expect(searchSpy).toBeCalledWith();
    expect(queryChangeSpy).toBeCalledWith(undefined);
});
