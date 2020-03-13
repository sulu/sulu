// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import {extendObservable as mockExtendObservable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import MultiAutoCompleteComponent from '../../../components/MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {ids: [], items: []});
}));

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    expect(render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    )).toMatchSnapshot();
});

test('Pass loading flag if MultiSelectionStore and SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').prop('loading')).toEqual(true);
});

test('Pass loading flag if only SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').prop('loading')).toEqual(true);
});

test('Pass loading flag if only MultiSelectionStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').prop('loading')).toEqual(true);
});

test('Pass allowAdd and idProperty prop to component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {});

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
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
    SearchStore.mockImplementation(function() {
        this.searchResults = suggestions;
        this.loading = false;
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    multiAutoComplete.find(MultiAutoCompleteComponent).instance().inputValue = 'James';
    multiAutoComplete.update();

    expect(multiAutoComplete.find('MultiAutoComplete').find('Suggestion').at(0).prop('value'))
        .toEqual(suggestions[0]);
    expect(multiAutoComplete.find('MultiAutoComplete').find('Suggestion').at(1).prop('value'))
        .toEqual(suggestions[1]);
});

test('Render with given value', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    expect(multiAutoComplete.render()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = mount(
        <MultiAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    expect(multiAutoComplete.render()).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    multiAutoComplete.find('MultiAutoComplete').simulate('search', 'James');

    expect(multiAutoComplete.instance().searchStore.search).toBeCalledWith('James', []);
});

test('Search using store with excluded-ids when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    // $FlowFixMe
    selectionStore.ids = [1, 3];
    multiAutoComplete.find('MultiAutoComplete').simulate('search', 'James');

    expect(multiAutoComplete.instance().searchStore.search).toBeCalledWith('James', [1, 3]);
});

test('Clear search result when chosen option has been selected with idProperty', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    const selectionStore = new MultiSelectionStore('contact', []);

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            idProperty="number"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    multiAutoComplete.find('MultiAutoComplete > MultiAutoComplete').prop('onChange')(data);
    expect(selectionStore.set).toBeCalledWith(data);

    expect(multiAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {});

    const selectionStore = new MultiSelectionStore('contact', []);

    shallow(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            options={{country: 'US'}}
            searchProperties={['firstName', 'lastName']}
            selectionStore={selectionStore}
        />
    );

    expect(SearchStore).toBeCalledWith('contact', ['firstName', 'lastName'], {country: 'US'});
});
