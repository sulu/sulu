// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleAutoComplete from '../SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../components/SingleAutoComplete', () => {
    const React = require('react');

    return jest.fn(function SingleAutoCompleteMock({loading, onChange, onSearch, value}) {
        function handleSearchClick() {
            onSearch('James');
        }

        function handleChangeClick() {
            onChange({id: 7, name: 'James Bond', number: '007'});
        }

        return React.createElement(
            'div',
            undefined,
            React.createElement('button', {onClick: handleSearchClick, type: 'button'}, 'search'),
            React.createElement('button', {onClick: handleChangeClick, type: 'button'}, 'change'),
            React.createElement('span', {'data-testid': 'loading'}, loading ? 'true' : 'false'),
            React.createElement('span', {'data-testid': 'value'}, value ? value.name : '')
        );
    });
});

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

const singleAutoCompleteComponent = ((jest.requireMock('../../../components/SingleAutoComplete'): any): {
    mock: {calls: Array<[Object]>},
    ...
});
const searchStoreMock = (SearchStore: any);

function getLastSingleAutoCompleteProps(): any {
    return getLatestMockProps(singleAutoCompleteComponent);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render in loading state when SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLastSingleAutoCompleteProps().loading).toBeTruthy();
});

test('Render in loading state when SingleSelectionStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.loading = true;

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLastSingleAutoCompleteProps().loading).toBeTruthy();
});

test('Render with loaded suggestions', () => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];

    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = suggestions;
        this.loading = false;
    });

    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    expect(getLastSingleAutoCompleteProps().suggestions).toEqual(suggestions);
});

test('Render with value of given SingleSelectionStore', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    const {asFragment} = render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    const {asFragment} = render(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from SingleAutoComplete component', async() => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });
    const user = userEvent.setup();
    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('button', {name: 'search'}));

    expect(getLastSingleAutoCompleteProps().onSearch).toBeDefined();
    expect(getLastSingleAutoCompleteProps().suggestions).toEqual([]);
    expect(((searchStoreMock.mock.instances[0]: any): {search: Function}).search).toHaveBeenCalledWith('James');
});

test('Call set item to SingleSelectionStore and clear search result when chosen option has changed', async() => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });
    const user = userEvent.setup();
    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('button', {name: 'change'}));

    expect(selectionStore.set).toHaveBeenCalledWith({id: 7, name: 'James Bond', number: '007'});
    expect(
        ((searchStoreMock.mock.instances[0]: any): {clearSearchResults: Function}).clearSearchResults
    ).toHaveBeenCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const locale = observable.box('cz');
    const selectionStore = new SingleSelectionStore('tags', undefined, locale);

    render(
        <SingleAutoComplete
            displayProperty="name"
            options={{country: 'US'}}
            searchProperties={['firstName', 'lastName']}
            selectionStore={selectionStore}
        />
    );

    expect(SearchStore).toHaveBeenCalledWith('tags', ['firstName', 'lastName'], {country: 'US'}, locale);
});
