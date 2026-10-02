// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchField from '../SearchField';

jest.mock('sulu-admin-bundle/utils/Translator');

const noop = jest.fn();

class SearchFieldHarness extends React.Component<Object, {query: string}> {
    state = {
        query: '',
    };

    handleQueryChange = (query: ?string) => {
        this.setState({query: query || ''});
        this.props.onQueryChange(query);
    };

    render() {
        return (
            <SearchField
                onQueryChange={this.handleQueryChange}
                onSearch={noop}
                onSearchResourceChange={noop}
                query={this.state.query}
                resourceKey={undefined}
                searchResources={undefined}
            />
        );
    }
}

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
    const user = userEvent.setup();
    const searchResourceChangeSpy = jest.fn();
    const searchSpy = jest.fn();

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

    render(
        <SearchField
            onQueryChange={jest.fn()}
            onSearch={searchSpy}
            onSearchResourceChange={searchResourceChangeSpy}
            resourceKey="page"
            searchResources={searchResources}
        />
    );

    expect(screen.queryByRole('button', {name: 'Contact'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Page/}));
    await user.click(screen.getByRole('button', {name: 'Contact'}));

    expect(searchResourceChangeSpy).toHaveBeenCalledWith('contact');
    expect(searchSpy).toHaveBeenCalledWith();
});

test('Call callback when query changes', async() => {
    const user = userEvent.setup();
    const queryChangeSpy = jest.fn();

    render(<SearchFieldHarness onQueryChange={queryChangeSpy} />);

    await user.type(screen.getByRole('textbox'), 'test');

    expect(queryChangeSpy).toHaveBeenLastCalledWith('test');
});

test('Call search with query when enter is pressed', async() => {
    const user = userEvent.setup();
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

    await user.type(screen.getByRole('textbox'), '{enter}');

    expect(searchSpy).toHaveBeenCalledWith();
});

test('Do not call search when other key than enter is pressed', async() => {
    const user = userEvent.setup();
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

    await user.type(screen.getByRole('textbox'), 'a');

    expect(searchSpy).not.toHaveBeenCalledWith();
});

test('Call search with query when search icon is clicked', async() => {
    const user = userEvent.setup();
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

    await user.click(screen.getByRole('button', {name: 'su-search'}));

    expect(searchSpy).toHaveBeenCalledWith();
});

test('Remove query when clear icon is clicked', async() => {
    const user = userEvent.setup();
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

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(searchSpy).toHaveBeenCalledWith();
    expect(queryChangeSpy).toHaveBeenCalledWith(undefined);
});
