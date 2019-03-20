// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import SingleAutoComplete from '../SingleAutoComplete';
import SingleAutoCompleteComponent from '../../../components/SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
        />
    )).toMatchSnapshot();
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

    const singleAutoComplete = mount(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={['name', 'number']}
            value={undefined}
        />
    );

    singleAutoComplete.find(SingleAutoCompleteComponent).instance().inputValue = 'James';
    singleAutoComplete.update();

    expect(singleAutoComplete.find('SingleAutoComplete').find('Suggestion').at(0).prop('value'))
        .toEqual(suggestions[0]);
    expect(singleAutoComplete.find('SingleAutoComplete').find('Suggestion').at(1).prop('value'))
        .toEqual(suggestions[1]);
});

test('Render with given value', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={{name: 'James Bond', number: '007'}}
        />
    )).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={{name: 'James Bond', number: '007'}}
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

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    singleAutoComplete.find('SingleAutoComplete').simulate('search', 'James');

    expect(singleAutoComplete.instance().searchStore.search).toBeCalledWith('James');
});

test('Call onChange and clear search result when chosen option has changed', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    const changeSpy = jest.fn();

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    const singleAutoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    singleAutoComplete.find('SingleAutoComplete').simulate('change', data);

    expect(changeSpy).toBeCalledWith(data);
    expect(singleAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            options={{country: 'US'}}
            resourceKey="contact"
            searchProperties={['firstName', 'lastName']}
            value={undefined}
        />
    );

    expect(SearchStore).toBeCalledWith('contact', ['firstName', 'lastName'], {country: 'US'});
});
