// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchField from '../SearchField';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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

test('Render without selected searchResource', () => {
    const {asFragment} = render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with selected and query', () => {
    const {asFragment} = render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey="page"
            searchResources={searchResources}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Call callback when searchResource changes', async() => {
    const searchResourceChangeSpy = jest.fn();
    const onSearch = jest.fn();
    render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={onSearch}
            onSearchResourceChange={searchResourceChangeSpy}
            resourceKey="page"
            searchResources={searchResources}
        />
    );
    const searchResourceButton = screen.getByRole('button', {name: /Page/i});

    expect(screen.queryByRole('button', {name: 'Contact'})).not.toBeInTheDocument();

    await userEvent.click(searchResourceButton);
    const contactButton = screen.getByRole('button', {name: 'Contact'});
    await userEvent.click(contactButton);

    expect(screen.queryByRole('button', {name: 'Contact'})).not.toBeInTheDocument();

    expect(searchResourceChangeSpy).toBeCalledWith('contact');
    expect(onSearch).toBeCalledWith();
});

test('Call callback when query changes', async() => {
    const queryChangeSpy = jest.fn();

    render(
        <SearchField
            onQueryChange={queryChangeSpy}
            onSearch={jest.fn()}
            onSearchResourceChange={jest.fn()}
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    const input = screen.getByRole('textbox');
    await userEvent.click(input);
    await userEvent.paste('test');

    expect(queryChangeSpy).toBeCalledWith('test');
});

test('Call search with query when enter is pressed', async() => {
    const searchSpy = jest.fn();

    render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    await userEvent.type(screen.getByRole('textbox'), '{enter}');

    expect(searchSpy).toBeCalledWith();
});

test('Do not call search when other key than enter is pressed', async() => {
    const searchSpy = jest.fn();

    render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    await userEvent.type(screen.getByRole('textbox'), 'a');

    expect(searchSpy).not.toBeCalledWith();
});

test('Call search with query when search icon is clicked', async() => {
    const searchSpy = jest.fn();

    render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    await userEvent.click(screen.getByLabelText('su-search'));

    expect(searchSpy).toBeCalledWith();
});

test('Remove query when clear icon is clicked', async() => {
    const searchSpy = jest.fn();
    const queryChangeSpy = jest.fn();

    render(
        <SearchField
            onQueryChange={queryChangeSpy}
            onSearch={searchSpy}
            onSearchResourceChange={jest.fn()}
            query="Test"
            resourceKey={undefined}
            searchResources={undefined}
        />
    );

    await userEvent.click(screen.getByLabelText('su-times'));

    expect(searchSpy).toBeCalledWith();
    expect(queryChangeSpy).toBeCalledWith(undefined);
});
