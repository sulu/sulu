// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import MultiAutoComplete from '../MultiAutoComplete';
import MultiAutoCompleteComponent from '../../../components/MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <MultiAutoComplete
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

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={['name', 'number']}
            value={undefined}
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

    const value = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    expect(render(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={value}
        />
    )).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    multiAutoComplete.find('MultiAutoComplete').simulate('search', 'James');

    expect(multiAutoComplete.instance().searchStore.search).toBeCalledWith('James');
});

test('Call onChange and clear search result when chosen option has been selected', () => {
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

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    multiAutoComplete.find('MultiAutoComplete').simulate('change', data);

    expect(changeSpy).toBeCalledWith(data);
    expect(multiAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});
