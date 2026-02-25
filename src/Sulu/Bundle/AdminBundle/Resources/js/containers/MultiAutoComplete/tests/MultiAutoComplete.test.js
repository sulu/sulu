// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import loaderStyles from '../../../components/Loader/loader.scss';

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

jest.mock('../../../stores/SearchStore', () => jest.fn());

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function(resourceKey, selectedItemIds, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.requestParameters = undefined;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {
        ids: [],
        items: [],
    });
}));

const searchStoreMock = (SearchStore: any);

function mockSearchStore(
    {loading = false, searchResults = []}: {|loading?: boolean, searchResults?: Array<Object>|} = {}
) {
    searchStoreMock.mockImplementation(function() {
        this.searchResults = searchResults;
        this.loading = loading;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
    });
}

beforeEach(() => {
    jest.clearAllMocks();
    mockSearchStore();
});

test('Render in loading state', () => {
    mockSearchStore({loading: true});

    const selectionStore = new MultiSelectionStore('contact', []);

    const {asFragment} = render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(asFragment()).toMatchSnapshot();
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

    expect(inputRefSpy).toBeCalledWith(screen.getByRole('textbox'));
});

test('Pass loading flag if MultiSelectionStore and SearchStore is loading', () => {
    mockSearchStore({loading: true});

    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Pass loading flag if only SearchStore is loading', () => {
    mockSearchStore({loading: true});

    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Pass loading flag if only MultiSelectionStore is loading', () => {
    mockSearchStore({loading: false});

    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Pass allowAdd and idProperty prop to component', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    const {asFragment} = render(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with loaded suggestions', async() => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];
    const user = userEvent.setup();

    mockSearchStore({searchResults: suggestions});

    const selectionStore = new MultiSelectionStore('contact', []);

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={['name', 'number']}
            selectionStore={selectionStore}
        />
    );

    await user.click(screen.getByRole('textbox'));
    expect(screen.getByRole('button', {name: /James Bond/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /John Doe/})).toBeInTheDocument();
});

test('Render with given value', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    const {asFragment} = render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    const {asFragment} = render(
        <MultiAutoComplete
            disabled={true}
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    expect(screen.getByRole('textbox')).toBeDisabled();
    expect(asFragment()).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', async() => {
    const selectionStore = new MultiSelectionStore('contact', []);
    const user = userEvent.setup();

    render(
        <MultiAutoComplete
            displayProperty="name"
            searchProperties={[]}
            selectionStore={selectionStore}
        />
    );

    const searchStore = searchStoreMock.mock.instances[0];

    await user.click(screen.getByRole('textbox'));
    await user.type(screen.getByRole('textbox'), 'James');

    expect(searchStore.search).toHaveBeenLastCalledWith('James', []);
});

test(
    'Search using store with excluded-ids when new search value is retrieved from MultiAutoComplete component',
    async() => {
        const selectionStore = new MultiSelectionStore('contact', []);
        (selectionStore: any).ids = [1, 3];
        const user = userEvent.setup();

        render(
            <MultiAutoComplete
                displayProperty="name"
                searchProperties={[]}
                selectionStore={selectionStore}
            />
        );

        const searchStore = searchStoreMock.mock.instances[0];

        await user.click(screen.getByRole('textbox'));
        await user.type(screen.getByRole('textbox'), 'James');

        expect(searchStore.search).toHaveBeenLastCalledWith('James', [1, 3]);
    }
);

test('Clear search result when chosen option has been selected with idProperty', async() => {
    const selectionStore = new MultiSelectionStore('contact', []);
    const user = userEvent.setup();
    mockSearchStore({searchResults: [{id: 7, name: 'James Bond', number: '007'}]});

    render(
        <MultiAutoComplete
            displayProperty="name"
            idProperty="number"
            searchProperties={['name']}
            selectionStore={selectionStore}
        />
    );

    const searchStore = searchStoreMock.mock.instances[0];

    await user.click(screen.getByRole('textbox'));
    await user.click(screen.getByRole('button', {name: /James Bond/}));

    expect(selectionStore.set).toHaveBeenCalledWith([{id: 7, name: 'James Bond', number: '007'}]);
    expect(searchStore.clearSearchResults).toHaveBeenCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    const locale = observable.box('de');
    const selectionStore = new MultiSelectionStore('contact', [], locale);
    selectionStore.requestParameters = {city: 'Vienna'};

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

    expect(SearchStore).toBeCalledWith(
        'contact',
        ['firstName', 'lastName'],
        {city: 'Vienna', country: 'US'},
        locale
    );
});
