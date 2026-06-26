// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

let mockMultiAutoCompleteProps: Object = {};
let mockSearchStoreInstances: Array<Object> = [];
let mockChangeValue: any;

const mockReact = require('react');

jest.mock('../../../stores/SearchStore', () => jest.fn());

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function(resourceKey, selectedItemIds, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.requestParameters = {};
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {
        ids: [],
        items: [],
    });
}));

jest.mock('../../../components/MultiAutoComplete', () => jest.fn((props) => {
    mockMultiAutoCompleteProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-disabled': props.disabled ? 'true' : 'false',
            'data-loading': props.loading ? 'true' : 'false',
            'data-testid': 'multi-auto-complete',
        },
        props.value.map((item) => mockReact.createElement('span', {key: item.id}, item[props.displayProperty])),
        props.suggestions.map((suggestion) => (
            mockReact.createElement('span', {key: 'suggestion-' + suggestion.id}, suggestion[props.displayProperty])
        )),
        mockReact.createElement('input', {'aria-label': 'input', ref: props.inputRef}),
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
    mockMultiAutoCompleteProps = {};
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

function renderMultiAutoComplete(props: Object = {}) {
    const selectionStore = props.selectionStore || new MultiSelectionStore('contact', []);

    return {
        selectionStore,
        ...render(
            <MultiAutoComplete
                displayProperty="name"
                searchProperties={[]}
                selectionStore={selectionStore}
                {...props}
            />
        ),
    };
}

test('Render in loading state', () => {
    mockSearchStore([], true);
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({selectionStore});

    expect(screen.getByTestId('multi-auto-complete')).toHaveAttribute('data-loading', 'true');
});

test('Should assign input as ref to inputRef', () => {
    const inputRefSpy = jest.fn();
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({
        inputRef: inputRefSpy,
        selectionStore,
    });

    expect(inputRefSpy).toHaveBeenCalledWith(screen.getByLabelText('input'));
});

test('Pass loading flag if MultiSelectionStore and SearchStore is loading', () => {
    mockSearchStore([], true);
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    renderMultiAutoComplete({selectionStore});

    expect(mockMultiAutoCompleteProps.loading).toEqual(true);
});

test('Pass loading flag if only SearchStore is loading', () => {
    mockSearchStore([], true);
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({selectionStore});

    expect(mockMultiAutoCompleteProps.loading).toEqual(true);
});

test('Pass loading flag if only MultiSelectionStore is loading', () => {
    const selectionStore = new MultiSelectionStore('contact', []);
    selectionStore.loading = true;

    renderMultiAutoComplete({selectionStore});

    expect(mockMultiAutoCompleteProps.loading).toEqual(true);
});

test('Pass allowAdd and idProperty prop to component', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        selectionStore,
    });

    expect(mockMultiAutoCompleteProps).toEqual(expect.objectContaining({
        allowAdd: true,
        idProperty: 'name',
    }));
});

test('Render with loaded suggestions', () => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];

    mockSearchStore(suggestions, false);
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({
        searchProperties: ['name', 'number'],
        selectionStore,
    });

    expect(mockMultiAutoCompleteProps.suggestions).toEqual(suggestions);
    expect(screen.getByText('James Bond')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
});

test('Render with given value', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({selectionStore});

    act(() => {
        selectionStore.items = [
            {id: 1, name: 'James Bond', number: '007'},
            {id: 2, name: 'John Doe', number: '005'},
        ];
    });

    expect(screen.getByText('James Bond')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
});

test('Render in disabled state', () => {
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({
        disabled: true,
        selectionStore,
    });

    act(() => {
        selectionStore.items = [
            {id: 1, name: 'James Bond', number: '007'},
            {id: 2, name: 'John Doe', number: '005'},
        ];
    });

    expect(screen.getByTestId('multi-auto-complete')).toHaveAttribute('data-disabled', 'true');
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', async() => {
    const selectionStore = new MultiSelectionStore('contact', []);

    renderMultiAutoComplete({selectionStore});

    await userEvent.click(screen.getByLabelText('search'));

    expect(mockSearchStoreInstances[0].search).toHaveBeenCalledWith('James', []);
});

test(
    'Search using store with excluded-ids when new search value is retrieved from MultiAutoComplete component',
    async() => {
        const selectionStore = new MultiSelectionStore('contact', []);
        (selectionStore: any).ids = [1, 3];

        renderMultiAutoComplete({selectionStore});

        await userEvent.click(screen.getByLabelText('search'));

        expect(mockSearchStoreInstances[0].search).toHaveBeenCalledWith('James', [1, 3]);
    }
);

test('Clear search result when chosen option has been selected with idProperty', async() => {
    const selectionStore = new MultiSelectionStore('contact', []);
    mockChangeValue = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    mockSearchStore([mockChangeValue], false);

    renderMultiAutoComplete({
        idProperty: 'number',
        selectionStore,
    });

    await userEvent.click(screen.getByLabelText('change'));

    expect(selectionStore.set).toHaveBeenCalledWith(mockChangeValue);
    expect(mockSearchStoreInstances[0].clearSearchResults).toHaveBeenCalledWith();
});

test('Construct SearchStore with correct parameters on mount', () => {
    const locale = observable.box('de');
    const selectionStore = new MultiSelectionStore('contact', [], locale);

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        options: {country: 'US'},
        searchProperties: ['firstName', 'lastName'],
        selectionStore,
    });

    expect(SearchStore).toHaveBeenCalledWith('contact', ['firstName', 'lastName'], {country: 'US'}, locale);
});
