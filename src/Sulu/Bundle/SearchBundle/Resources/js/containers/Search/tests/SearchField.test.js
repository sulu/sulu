// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import SearchField from '../SearchField';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createIndexes() {
    return {
        contact: {
            icon: 'su-test',
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
        page: {
            icon: 'su-test',
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };
}

test('Render without selected index', () => {
    render(
        <SearchField
            indexes={undefined}
            indexName={undefined}
            onIndexChange={jest.fn()}
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
        />
    );

    expect(screen.getByRole('button', {name: /sulu_search\.everything/})).toBeInTheDocument();
});

test('Render with selected index and query', () => {
    render(
        <SearchField
            indexes={createIndexes()}
            indexName="page"
            onIndexChange={jest.fn()}
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
            query="Test"
        />
    );

    expect(screen.getByRole('button', {name: /Page/})).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveValue('Test');
});

test('Call callback when index changes', async() => {
    const user = userEvent.setup();
    const indexChangeSpy = jest.fn();

    render(
        <SearchField
            indexes={createIndexes()}
            indexName="page"
            onIndexChange={indexChangeSpy}
            onQueryChange={jest.fn()}
            onSearch={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: /Page/}));
    await user.click(screen.getByRole('button', {name: 'Contact'}));

    expect(indexChangeSpy).toBeCalledWith('contact');
});

test('Call callback when query changes', async() => {
    const user = userEvent.setup();
    const queryChangeSpy = jest.fn();

    const SearchFieldWrapper = () => {
        const [query, setQuery] = React.useState('');
        const handleQueryChange = React.useCallback((value) => {
            setQuery(value || '');
            queryChangeSpy(value);
        }, []);

        return (
            <SearchField
                indexes={undefined}
                indexName={undefined}
                onIndexChange={jest.fn()}
                onQueryChange={handleQueryChange}
                onSearch={jest.fn()}
                query={query}
            />
        );
    };

    render(<SearchFieldWrapper />);

    await user.type(screen.getByRole('textbox'), 'test');
    expect(queryChangeSpy).toHaveBeenLastCalledWith('test');
});

test('Call search when enter is pressed', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();

    render(
        <SearchField
            indexes={undefined}
            indexName={undefined}
            onIndexChange={jest.fn()}
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            query="Test"
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.keyboard('{Enter}');

    expect(searchSpy).toBeCalledWith();
});

test('Do not call search when key other than enter is pressed', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();

    render(
        <SearchField
            indexes={undefined}
            indexName={undefined}
            onIndexChange={jest.fn()}
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            query="Test"
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.keyboard('a');

    expect(searchSpy).not.toBeCalled();
});

test('Call search when search icon is clicked', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();

    render(
        <SearchField
            indexes={undefined}
            indexName={undefined}
            onIndexChange={jest.fn()}
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            query="Test"
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-search'}));

    expect(searchSpy).toBeCalledWith();
});

test('Remove query when clear icon is clicked', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();
    const queryChangeSpy = jest.fn();

    render(
        <SearchField
            indexes={undefined}
            indexName={undefined}
            onIndexChange={jest.fn()}
            onQueryChange={queryChangeSpy}
            onSearch={searchSpy}
            query="Test"
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(searchSpy).toBeCalledWith();
    expect(queryChangeSpy).toBeCalledWith(undefined);
});
