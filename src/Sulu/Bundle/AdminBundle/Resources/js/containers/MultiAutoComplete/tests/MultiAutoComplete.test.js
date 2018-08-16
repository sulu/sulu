// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import MultiAutoCompleteComponent from '../../../components/MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import SelectionStore from '../../../stores/SelectionStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/SelectionStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = true;
        mockExtendObservable(this, {
            items: [],
        });
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

test('Pass loading flag if SelectionStore and SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = true;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
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

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').prop('loading')).toEqual(true);
});

test('Pass loading flag if only SelectionStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = true;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
        />
    );

    expect(multiAutoComplete.find('MultiAutoComplete').prop('loading')).toEqual(true);
});

test('Pass allowAdd and idProperty prop to component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {});

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = shallow(
        <MultiAutoComplete
            allowAdd={true}
            displayProperty="name"
            idProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
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

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
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

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={[1, 2]}
        />
    );

    expect(SelectionStore).toBeCalledWith('test', [1, 2], undefined, 'ids');
    multiAutoComplete.instance().selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    expect(multiAutoComplete.render()).toMatchSnapshot();
});

test('Pass filterParameter to SelectionStore', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    mount(
        <MultiAutoComplete
            displayProperty="name"
            filterParameter="names"
            onChange={jest.fn()}
            resourceKey="tags"
            searchProperties={[]}
            value={[1, 2]}
        />
    );

    expect(SelectionStore).toBeCalledWith('tags', [1, 2], undefined, 'names');
});

test('Pass locale to SelectionStore', () => {
    const locale = observable.box('en');
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    mount(
        <MultiAutoComplete
            displayProperty="name"
            filterParameter="names"
            locale={locale}
            onChange={jest.fn()}
            resourceKey="tags"
            searchProperties={[]}
            value={[1, 2]}
        />
    );

    expect(SelectionStore).toBeCalledWith('tags', [1, 2], locale, 'names');
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
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

    expect(multiAutoComplete.instance().searchStore.search).toBeCalledWith('James', []);
});

test('Search using store with excluded ids when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
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

    multiAutoComplete.instance().selectionStore.items = [
        {id: 1},
        {id: 3},
    ];
    multiAutoComplete.find('MultiAutoComplete').simulate('search', 'James');

    expect(multiAutoComplete.instance().searchStore.search).toBeCalledWith('James', [1, 3]);
});

test('Call onChange and clear search result when chosen option has been selected', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const changeSpy = jest.fn();

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={[]}
        />
    );

    multiAutoComplete.find('MultiAutoComplete > MultiAutoComplete').prop('onChange')(data);
    expect(multiAutoComplete.instance().selectionStore.set).toBeCalledWith(data);
    multiAutoComplete.instance().selectionStore.items = [data];

    expect(changeSpy).toBeCalledWith([7]);
    expect(multiAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});

test('Call onChange and clear search result when chosen option has been selected with idProperty', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const changeSpy = jest.fn();

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            idProperty="number"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={[]}
        />
    );

    multiAutoComplete.find('MultiAutoComplete > MultiAutoComplete').prop('onChange')(data);
    expect(multiAutoComplete.instance().selectionStore.set).toBeCalledWith(data);
    multiAutoComplete.instance().selectionStore.items = [data];

    expect(changeSpy).toBeCalledWith(['007']);
    expect(multiAutoComplete.instance().searchStore.clearSearchResults).toBeCalledWith();
});

test('Should call disposer when component unmounts', () => {
    const suggestions = [
        {id: 7, number: '007', name: 'James Bond'},
        {id: 6, number: '006', name: 'John Doe'},
    ];

    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = suggestions;
        this.loading = false;
    });

    // $FlowFixMe
    SelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
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

    const changeDisposerSpy = jest.fn();
    multiAutoComplete.instance().changeDisposer = changeDisposerSpy;
    multiAutoComplete.unmount();

    expect(changeDisposerSpy).toBeCalledWith();
});
