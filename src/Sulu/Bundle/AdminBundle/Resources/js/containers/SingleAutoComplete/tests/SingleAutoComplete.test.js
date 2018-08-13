// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import SingleAutoComplete from '../SingleAutoComplete';
import SingleAutoCompleteComponent from '../../../components/SingleAutoComplete';
import AutoCompleteStore from '../stores/AutoCompleteStore';

jest.mock('../stores/AutoCompleteStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
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
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = suggestions;
        this.loading = false;
    });

    const autoComplete = mount(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={['name', 'number']}
            value={undefined}
        />
    );

    autoComplete.find(SingleAutoCompleteComponent).instance().inputValue = 'James';
    autoComplete.update();

    expect(autoComplete.find('SingleAutoComplete').find('Suggestion').at(0).prop('value')).toEqual(suggestions[0]);
    expect(autoComplete.find('SingleAutoComplete').find('Suggestion').at(1).prop('value')).toEqual(suggestions[1]);
});

test('Render with given value', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
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

test('Search using store when new search value is retrieved from SingleAutoComplete component', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const autoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    autoComplete.find('SingleAutoComplete').simulate('search', 'James');

    expect(autoComplete.instance().autoCompleteStore.search).toBeCalledWith('James');
});

test('Call onChange and clear search result when chosen option has changed', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
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

    const autoComplete = shallow(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    autoComplete.find('SingleAutoComplete').simulate('change', data);

    expect(changeSpy).toBeCalledWith(data);
    expect(autoComplete.instance().autoCompleteStore.clearSearchResults).toBeCalledWith();
});
