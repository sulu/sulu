// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleAutoComplete from '../SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import loaderStyles from '../../../components/Loader/loader.scss';

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

const searchStoreMock = (SearchStore: any);

function mockSearchStore(searchResults: Array<Object> = [], loading: boolean = false) {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = searchResults;
        this.loading = loading;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render in loading state when SearchStore is loading', () => {
    mockSearchStore([], true);

    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Render in loading state when SingleSelectionStore is loading', () => {
    mockSearchStore();

    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.loading = true;

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Render with loaded suggestions', async() => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];
    const user = userEvent.setup();

    mockSearchStore(suggestions, false);

    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('textbox'));
    expect(screen.getByRole('button', {name: /James Bond/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /John Doe/})).toBeInTheDocument();
});

test('Render with value of given SingleSelectionStore', () => {
    mockSearchStore([], true);

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
    mockSearchStore([], true);

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
    mockSearchStore([], false);
    const user = userEvent.setup();
    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.type(screen.getByRole('textbox'), 'James');

    expect(((searchStoreMock.mock.instances[0]: any): {search: Function}).search).toHaveBeenLastCalledWith('James');
});

test('Call set item to SingleSelectionStore and clear search result when chosen option has changed', async() => {
    mockSearchStore([
        {id: 7, name: 'James Bond', number: '007'},
    ], false);
    const user = userEvent.setup();
    const selectionStore = new SingleSelectionStore('tags');

    render(
        <SingleAutoComplete
            displayProperty="name"
            searchProperties={['name']}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.click(screen.getByRole('button', {name: /James Bond/}));

    expect(selectionStore.set).toHaveBeenCalledWith({id: 7, name: 'James Bond', number: '007'});
    expect(
        ((searchStoreMock.mock.instances[0]: any): {clearSearchResults: Function}).clearSearchResults
    ).toHaveBeenCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    mockSearchStore([], false);

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
