// @flow
import React from 'react';
import {mount, shallow, render} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiAutoComplete from '../MultiAutoComplete';
import MultiAutoCompleteComponent from '../../../components/MultiAutoComplete';
import SearchStore from '../../../stores/SearchStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

jest.mock('../../../stores/SearchStore', () => jest.fn());
jest.mock('../../../stores/MultiSelectionStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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

test('Pass loading flag if MultiSelectionStore and SearchStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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

test('Pass loading flag if only MultiSelectionStore is loading', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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

    expect(MultiSelectionStore).toBeCalledWith('test', [1, 2], undefined, 'ids');
    multiAutoComplete.instance().selectionStore.items = [
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

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const multiAutoComplete = mount(
        <MultiAutoComplete
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={[1, 2]}
        />
    );

    expect(MultiSelectionStore).toBeCalledWith('test', [1, 2], undefined, 'ids');
    multiAutoComplete.instance().selectionStore.items = [
        {id: 1, name: 'James Bond', number: '007'},
        {id: 2, name: 'John Doe', number: '005'},
    ];

    expect(multiAutoComplete.render()).toMatchSnapshot();
});

test('Do not load items if passed value has not changed', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.items = [];
        this.loadItems = jest.fn();
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

    multiAutoComplete.setProps({displayProperty: 'title'});

    expect(multiAutoComplete.instance().selectionStore.loadItems).not.toBeCalled();
});

test('Load items if passed value has changed', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.items = [];
        this.loadItems = jest.fn();
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

    multiAutoComplete.setProps({value: undefined});

    expect(multiAutoComplete.instance().selectionStore.loadItems).toBeCalledWith(undefined);
});

test('Pass filterParameter to MultiSelectionStore', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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

    expect(MultiSelectionStore).toBeCalledWith('tags', [1, 2], undefined, 'names');
});

test('Pass locale to MultiSelectionStore', () => {
    const locale = observable.box('en');
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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

    expect(MultiSelectionStore).toBeCalledWith('tags', [1, 2], locale, 'names');
});

test('Search using store when new search value is retrieved from MultiAutoComplete component', () => {
    // $FlowFixMe
    SearchStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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
    MultiSelectionStore.mockImplementation(function() {
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

test('Should call the onChange callback if the value of the selection-store changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const changeSpy = jest.fn();

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={[]}
        />
    );

    multiAutoComplete.instance().selectionStore.items = [{id: 22}, {id: 23}];
    expect(changeSpy).toBeCalledWith([22, 23]);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementation(function() {
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            items: [],
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const multiAutoComplete = mount(
        <MultiAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={[]}
        />
    );

    // change callback should be called when item of the store mock changes
    multiAutoComplete.instance().selectionStore.items = [{id: 22}, {id: 23}];
    expect(changeSpy).toBeCalledWith([22, 23]);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
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
    MultiSelectionStore.mockImplementation(function() {
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
