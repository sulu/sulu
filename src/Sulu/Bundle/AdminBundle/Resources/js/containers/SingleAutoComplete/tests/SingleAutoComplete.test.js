// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import {extendObservable as mockExtendObservable} from 'mobx';
import SingleAutoComplete from '../SingleAutoComplete';
import SingleAutoCompleteComponent from '../../../components/SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: undefined});
}));

test('Render in loading state when SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(singleAutoComplete.find('SingleAutoComplete').prop('loading')).toBeTruthy();
});

test('Render in loading state when SingleSelectionStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.loading = true;

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(singleAutoComplete.find('SingleAutoComplete').prop('loading')).toBeTruthy();
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

    const singleAutoComplete = mount(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    singleAutoComplete.find(SingleAutoCompleteComponent).instance().inputValue = 'James';
    singleAutoComplete.update();

    expect(singleAutoComplete.find('SingleAutoComplete').find('Suggestion').at(0).prop('value'))
        .toEqual(suggestions[0]);
    expect(singleAutoComplete.find('SingleAutoComplete').find('Suggestion').at(1).prop('value'))
        .toEqual(suggestions[1]);
});

test('Render with value of given SingleSelectionStore', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {name: 'James Bond', number: '007'};

    expect(render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    )).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {name: 'James Bond', number: '007'};

    expect(render(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    )).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from SingleAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const selectionStore = new SingleSelectionStore('tags');

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    singleAutoComplete.find('SingleAutoComplete').simulate('search', 'James');

    expect(singleAutoComplete.instance().searchStore.search).toBeCalledWith('James');
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

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    singleAutoComplete.find('SingleAutoComplete').simulate('change', data);

    expect(selectionStore.set).toBeCalledWith(data);
    expect(singleAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const selectionStore = new SingleSelectionStore('tags');

    shallow(
        <SingleAutoComplete
            displayProperty="name"
            options={{country: 'US'}}
            searchProperties={['firstName', 'lastName']}
            selectionStore={selectionStore}
        />
    );

    expect(SearchStore).toBeCalledWith('tags', ['firstName', 'lastName'], {country: 'US'});
});
