// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleAutoComplete from '../SingleAutoComplete';
import SingleAutoCompleteComponent from '../../../components/SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../components/SingleAutoComplete', () => jest.fn(() => null));
jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

function getLatestSingleAutoCompleteProps() {
    const calls = (SingleAutoCompleteComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getFirstSearchStoreMockInstance() {
    return (SearchStore: any).mock.instances[0];
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

    expect(getLatestSingleAutoCompleteProps().loading).toBeTruthy();
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

    expect(getLatestSingleAutoCompleteProps().loading).toBeTruthy();
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

    expect(getLatestSingleAutoCompleteProps().suggestions).toEqual(suggestions);
});

test('Render with value of given SingleSelectionStore', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestSingleAutoCompleteProps().value).toEqual({id: 7, name: 'James Bond', number: '007'});
});

test('Render in disabled state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    render(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestSingleAutoCompleteProps().disabled).toEqual(true);
    expect(getLatestSingleAutoCompleteProps().value).toEqual({id: 7, name: 'James Bond', number: '007'});
});

test('Search using store when new search value is retrieved from SingleAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    getLatestSingleAutoCompleteProps().onSearch('James');

    expect(getFirstSearchStoreMockInstance().search).toBeCalledWith('James');
});

test('Call set item to SingleSelectionStore and clear search result when chosen option has changed', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new SingleSelectionStore('tags');

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    getLatestSingleAutoCompleteProps().onChange(data);

    expect(selectionStore.set).toBeCalledWith(data);
    expect(getFirstSearchStoreMockInstance().clearSearchResults).toBeCalledWith();
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

    expect(SearchStore).toBeCalledWith('tags', ['firstName', 'lastName'], {country: 'US'}, locale);
});
