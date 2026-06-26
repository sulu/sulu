// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleAutoComplete from '../SingleAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';

let mockSingleAutoCompleteProps: Object = {};
let mockSearchStoreInstances: Array<Object> = [];
let mockChangeValue: ?Object;

const mockReact = require('react');

jest.mock('../../../stores/SearchStore', () => jest.fn());

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

jest.mock('../../../components/SingleAutoComplete', () => jest.fn((props) => {
    mockSingleAutoCompleteProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-disabled': props.disabled ? 'true' : 'false',
            'data-loading': props.loading ? 'true' : 'false',
            'data-testid': 'single-auto-complete',
        },
        props.value && mockReact.createElement('span', {}, props.value[props.displayProperty]),
        props.suggestions.map((suggestion) => (
            mockReact.createElement('span', {key: suggestion.id}, suggestion[props.displayProperty])
        )),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'search',
                onClick: () => props.onSearch('James'),
                type: 'button',
            },
            'Search'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'change',
                onClick: () => props.onChange(mockChangeValue),
                type: 'button',
            },
            'Change'
        )
    );
}));

beforeEach(() => {
    jest.clearAllMocks();
    mockSingleAutoCompleteProps = {};
    mockSearchStoreInstances = [];
    mockChangeValue = undefined;

    mockSearchStore([], false);
});

function mockSearchStore(searchResults, loading) {
    (SearchStore: any).mockImplementation(function(resourceKey, searchProperties, options, locale) {
        this.resourceKey = resourceKey;
        this.searchProperties = searchProperties;
        this.options = options;
        this.locale = locale;
        this.searchResults = searchResults;
        this.loading = loading;
        this.search = jest.fn();
        this.clearSearchResults = jest.fn();
        mockSearchStoreInstances.push(this);
    });
}

function renderSingleAutoComplete(props: Object = {}) {
    const selectionStore = props.selectionStore || new SingleSelectionStore('tags');

    return {
        selectionStore,
        ...render(
            <SingleAutoComplete
                displayProperty="name"
                searchProperties={[]}
                selectionStore={selectionStore}
                {...props}
            />
        ),
    };
}

test('Render in loading state when SearchStore is loading', () => {
    mockSearchStore([], true);
    const selectionStore = new SingleSelectionStore('tags');

    renderSingleAutoComplete({selectionStore});

    expect(mockSingleAutoCompleteProps.loading).toBeTruthy();
});

test('Render in loading state when SingleSelectionStore is loading', () => {
    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.loading = true;

    renderSingleAutoComplete({selectionStore});

    expect(mockSingleAutoCompleteProps.loading).toBeTruthy();
});

test('Render with loaded suggestions', () => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];

    mockSearchStore(suggestions, false);
    const selectionStore = new SingleSelectionStore('tags');

    renderSingleAutoComplete({
        searchProperties: ['name', 'number'],
        selectionStore,
    });

    expect(mockSingleAutoCompleteProps.suggestions).toEqual(suggestions);
    expect(screen.getByText('James Bond')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
});

test('Render with value of given SingleSelectionStore', () => {
    mockSearchStore([], true);
    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    renderSingleAutoComplete({selectionStore});

    expect(mockSingleAutoCompleteProps.value).toEqual({id: 7, name: 'James Bond', number: '007'});
    expect(screen.getByText('James Bond')).toBeInTheDocument();
});

test('Render in disabled state', () => {
    mockSearchStore([], true);
    const selectionStore = new SingleSelectionStore('tags');
    selectionStore.item = {id: 7, name: 'James Bond', number: '007'};

    renderSingleAutoComplete({
        disabled: true,
        selectionStore,
    });

    expect(screen.getByTestId('single-auto-complete')).toHaveAttribute('data-disabled', 'true');
});

test('Search using store when new search value is retrieved from SingleAutoComplete component', async() => {
    const selectionStore = new SingleSelectionStore('tags');

    renderSingleAutoComplete({selectionStore});

    await userEvent.click(screen.getByLabelText('search'));

    expect(mockSearchStoreInstances[0].search).toHaveBeenCalledWith('James');
});

test('Call set item to SingleSelectionStore and clear search result when chosen option has changed', async() => {
    const selectionStore = new SingleSelectionStore('tags');
    mockChangeValue = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    mockSearchStore([mockChangeValue], false);

    renderSingleAutoComplete({selectionStore});

    await userEvent.click(screen.getByLabelText('change'));

    expect(selectionStore.set).toHaveBeenCalledWith(mockChangeValue);
    expect(mockSearchStoreInstances[0].clearSearchResults).toHaveBeenCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    const locale = observable.box('cz');
    const selectionStore = new SingleSelectionStore('tags', undefined, locale);

    renderSingleAutoComplete({
        options: {country: 'US'},
        searchProperties: ['firstName', 'lastName'],
        selectionStore,
    });

    expect(SearchStore).toHaveBeenCalledWith('tags', ['firstName', 'lastName'], {country: 'US'}, locale);
});
