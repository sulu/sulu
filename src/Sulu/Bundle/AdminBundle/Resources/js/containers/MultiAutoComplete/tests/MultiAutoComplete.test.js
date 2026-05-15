// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import MultiAutoCompleteComponent from '../../../components/MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

jest.mock('../../../components/MultiAutoComplete', () => {
    const React = require('react');

    return jest.fn(function MultiAutoComplete(props) {
        if (props.inputRef) {
            props.inputRef('input-ref');
        }

        return <div />;
    });
});

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function(resourceKey, selectedItemIds, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;
    this.requestParameters = {};

    mockExtendObservable(this, {
        ids: [],
        items: [],
    });
}));

function getLatestMultiAutoCompleteProps() {
    const calls = ((MultiAutoCompleteComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();

    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });
});

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementationOnce(function() {
        this.searchResults = [];
        this.loading = true;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().loading).toBe(true);
});

test('Should assign input as ref to inputRef', () => {
    const inputRefSpy = jest.fn();
    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            inputRef={inputRefSpy}
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(inputRefSpy).toBeCalledWith('input-ref');
});

test('Pass loading flag if MultiSelectionStore and SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementationOnce(function() {
        this.searchResults = [];
        this.loading = true;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().loading).toEqual(true);
});

test('Pass loading flag if only SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementationOnce(function() {
        this.searchResults = [];
        this.loading = true;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().loading).toEqual(true);
});

test('Pass loading flag if only MultiSelectionStore is loading', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().loading).toEqual(true);
});

test('Pass allowAdd and idProperty prop to component', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps()).toEqual(expect.objectContaining({
        allowAdd: true,
        idProperty: 'name',
    }));
});

test('Render with loaded suggestions', () => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];

    // $FlowFixMe
    SearchStore.mockImplementationOnce(function() {
        this.searchResults = suggestions;
        this.loading = false;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().suggestions).toEqual(suggestions);
});

test('Render with given value', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().value).toEqual(selectionStore.items);
});

test('Render in disabled state', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    render(
        <MultiAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(getLatestMultiAutoCompleteProps().disabled).toBe(true);
    expect(getLatestMultiAutoCompleteProps().value).toEqual(selectionStore.items);
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    getLatestMultiAutoCompleteProps().onSearch('James');

    expect(((SearchStore: any).mock.instances[0]: any).search).toBeCalledWith('James', []);
});

test('Search using store with excluded-ids when new search value is retrieved from MultiAutoComplete component', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    // $FlowFixMe
    selectionStore.ids = [1, 3];

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    getLatestMultiAutoCompleteProps().onSearch('James');

    expect(((SearchStore: any).mock.instances[0]: any).search).toBeCalledWith('James', [1, 3]);
});

test('Clear search result when chosen option has been selected with idProperty', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    render(
        <MultiAutoComplete
            displayProperty="name"
            idProperty="number"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    getLatestMultiAutoCompleteProps().onChange(data);
    expect(selectionStore.set).toBeCalledWith(data);

    expect(((SearchStore: any).mock.instances[0]: any).clearSearchResults).toBeCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    const locale = observable.box('de');
    const selectionStore = new MultiSelectionStore('contact', [], locale);

    render(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            options={{country: 'US'}}
            searchProperties={['firstName', 'lastName']}
            selectionStore={selectionStore}
        />
    );

    expect(SearchStore).toBeCalledWith('contact', ['firstName', 'lastName'], {country: 'US'}, locale);
});
