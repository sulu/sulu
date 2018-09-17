// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {observable} from 'mobx';
import pretty from 'pretty';
import MultiSelection from '../MultiSelection';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => function Datagrid() {
    return <div className="datagrid" />;
});

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function(resourceKey) {
    this.clearSelection = jest.fn();
    this.destroy = jest.fn();
    this.resourceKey = resourceKey;
    this.select = jest.fn();
    this.setActive = jest.fn();
}));

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function() {
    this.items = [];
    this.set = jest.fn();
    this.move = jest.fn();
    this.removeById = jest.fn();
    this.loadItems = jest.fn();
}));

beforeEach(() => {
    const body = document.body;

    if (body) {
        body.innerHTML = '';
    }
});

test('Show with default plus icon', () => {
    expect(render(
        <MultiSelection adapter="table" onChange={jest.fn()} overlayTitle="Selection" resourceKey="snippets" />
    )).toMatchSnapshot();
});

test('Show with passed label', () => {
    expect(render(
        <MultiSelection
            adapter="column_list"
            label="Select Snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    )).toMatchSnapshot();
});

test('Show with passed icon', () => {
    expect(render(
        <MultiSelection
            adapter="table"
            icon="su-document"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    )).toMatchSnapshot();
});

test('Pass locale to MultiDatagridOverlay', () => {
    const locale = observable.box('de');
    const selection = mount(
        <MultiSelection
            adapter="table"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    expect(selection.find('MultiDatagridOverlay').prop('locale').get()).toEqual('de');
});

test('Pass disabledIds to MultiDatagridOverlay', () => {
    const disabledIds = [1, 2, 4];

    const selection = mount(
        <MultiSelection
            adapter="table"
            disabledIds={disabledIds}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    expect(selection.find('MultiDatagridOverlay').prop('disabledIds')).toEqual(disabledIds);
});

test('Show with passed values as items in right locale', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2'}, {id: 5, title: 'Title 5'}];
    });

    expect(render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1, 2, 5]}
        />
    )).toMatchSnapshot();

    expect(MultiSelectionStore).toBeCalledWith('snippets', [1, 2, 5], locale);
});

test('Should open an overlay', () => {
    const selection = mount(
        <MultiSelection adapter="table" onChange={jest.fn()} overlayTitle="Selection" resourceKey="snippets" />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const body = document.body;
    expect(pretty(body ? body.innerHTML : null)).toMatchSnapshot();
});

test('Should close an overlay using the close button', () => {
    const selection = mount(
        <MultiSelection
            adapter="table"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const closeButton = document.querySelector('.su-times');
    if (closeButton) {
        closeButton.click();
    }

    selection.update();
    expect(selection.find('MultiDatagridOverlay').prop('open')).toEqual(false);
});

test('Should close an overlay using the confirm button', () => {
    const selection = mount(
        <MultiSelection adapter="table" onChange={jest.fn()} overlayTitle="Selection" resourceKey="snippets" />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    selection.update();
    expect(selection.find('MultiDatagridOverlay').prop('open')).toEqual(false);
});

test('Should call the onChange callback when clicking the confirm button', () => {
    const changeSpy = jest.fn();
    const selection = mount(
        <MultiSelection adapter="table" onChange={changeSpy} overlayTitle="Selection" resourceKey="snippets" />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');
    const datagridStore = selection.find('MultiDatagridOverlay').instance().datagridStore;
    datagridStore.selections = [3, 7, 2];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(selection.instance().selectionStore.set).toBeCalledWith([3, 7, 2]);
});

test('Should not call the onChange callback when items have not changed', () => {
    const changeSpy = jest.fn();
    const selection = mount(
        <MultiSelection
            adapter="table"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1]}
        />
    );

    expect(changeSpy).not.toBeCalled();

    selection.instance().selectionStore.items = [{id: 1}];
    selection.setProps({value: [1]});
    expect(changeSpy).not.toBeCalled();
});

test('Should load the items if value prop changes', () => {
    const changeSpy = jest.fn();
    const selection = mount(
        <MultiSelection
            adapter="table"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1]}
        />
    );

    selection.setProps({value: [1, 3]});
    expect(selection.instance().selectionStore.loadItems).toBeCalledWith([1, 3]);
});

test('Should instantiate the DatagridStore with the correct resourceKey and destroy it on unmount', () => {
    const selection = mount(
        <MultiSelection adapter="table" onChange={jest.fn()} overlayTitle="Selection" resourceKey="pages" />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = selection.find('MultiDatagridOverlay').instance().datagridStore;
    expect(datagridStore.resourceKey).toEqual('pages');

    selection.unmount();
    expect(datagridStore.destroy).toBeCalled();
});

test('Should instantiate the DatagridStore with the preselected ids', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
    });

    const selection = mount(
        <MultiSelection
            adapter="table"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = selection.find('MultiDatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});
});

test('Should reinstantiate the DatagridStore with the preselected ids when new props are received', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = mount(
        <MultiSelection
            adapter="table"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = selection.find('MultiDatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});

    selection.setProps({value: [1, 3]});
    const loadItemsCall = selection.instance().selectionStore.loadItems.mock.calls[0];
    expect(loadItemsCall[0]).toEqual([1, 3]);
});

test('Should not reload items if none of the items changed', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = mount(
        <MultiSelection
            adapter="table"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    const datagridStore = selection.find('MultiDatagridOverlay').instance().datagridStore;
    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 5});
    expect(datagridStore.select).toBeCalledWith({id: 8});

    selection.setProps({value: [1, 5, 8]});
    expect(selection.instance().selectionStore.loadItems).not.toBeCalled();
});

test('Should not reinstantiate the DatagridStore with the preselected ids when new props have the same values', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = mount(
        <MultiSelection
            adapter="table"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    selection.find('Button[icon="su-plus"]').simulate('click');

    selection.setProps({value: [1, 5, 8]});
    expect(selection.instance().selectionStore.loadItems).not.toBeCalled();
});

test('Should remove an item when the remove button is clicked', () => {
    const changeSpy = jest.fn();
    const selection = shallow(
        <MultiSelection
            adapter="table"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[3, 7, 9]}
        />
    );

    selection.find('MultiItemSelection').prop('onItemRemove')(7);
    expect(selection.instance().selectionStore.removeById).toBeCalledWith(7);
});

test('Should reorder the items on drag and drop', () => {
    const changeSpy = jest.fn();
    const selection = shallow(
        <MultiSelection
            adapter="table"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[3, 7, 9]}
        />
    );

    selection.find('MultiItemSelection').prop('onItemsSorted')(1, 2);

    expect(selection.instance().selectionStore.move).toBeCalledWith(1, 2);
});
