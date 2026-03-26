// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiSelection from '../MultiSelection';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import MultiItemSelection from '../../../components/MultiItemSelection';
import PublishIndicator from '../../../components/PublishIndicator';
import MultiListOverlay from '../../../containers/MultiListOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/List', () => function List() {
    return <div className="list" />;
});

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions) {
        this.clearSelection = jest.fn();
        this.destroy = jest.fn();
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.setActive = jest.fn();
        this.clear = jest.fn();
        this.selections = [];

        mockExtendObservable(this, {
            selectionIds: [],
        });
    }
));

jest.mock('../../../containers/MultiListOverlay', () => {
    const React = require('react');
    const ListStore: any = require('../../../containers/List/stores/ListStore');
    const listStores = [];

    const MockMultiListOverlay: any = jest.fn((props) => {
        const observableOptions = {};
        if (props.locale) {
            observableOptions.locale = props.locale;
        }

        const listStore = new ListStore(
            props.resourceKey,
            props.listKey,
            'multi_list_overlay',
            observableOptions,
            props.options
        );

        props.preSelectedItems.forEach((preSelectedItem) => {
            listStore.select(preSelectedItem);
        });

        listStores.push(listStore);

        React.useEffect(() => {
            return () => {
                listStore.destroy();
            };
        }, [listStore]);

        return null;
    });

    MockMultiListOverlay.__listStores = listStores;

    return MockMultiListOverlay;
});

jest.mock('../../../components/MultiItemSelection', () => {
    const React = require('react');
    const MultiItemSelection: any = jest.fn(({children, disabled, label, leftButton}) => (
        <div data-testid="multi-item-selection">
            <button disabled={disabled} onClick={leftButton?.onClick} type="button">
                {leftButton?.icon}
            </button>
            <span>{label}</span>
            {children}
        </div>
    ));

    MultiItemSelection.Item = jest.fn(({
        children,
        id,
    }) => (
        <button type="button">
            {id}
            {children}
        </button>
    ));

    return MultiItemSelection;
});

jest.mock('../../../components/PublishIndicator', () => jest.fn(() => null));

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function() {
    this.set = jest.fn();
    this.move = jest.fn();
    this.removeById = jest.fn();
    this.loadItems = jest.fn();
    this.setRequestParameters = jest.fn();

    mockExtendObservable(this, {
        items: [],
    });
}));

const MultiSelectionStoreMock = (MultiSelectionStore: any);
const MultiItemSelectionMock = (MultiItemSelection: any);
const MultiItemSelectionItemMock = (MultiItemSelection.Item: any);
const MultiListOverlayMock = (MultiListOverlay: any);
const PublishIndicatorMock = (PublishIndicator: any);

const getMockCallProps = (mockComponent) => mockComponent.mock.calls.map(([props]) => props);

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getLastMockCallPropsMatching = (mockComponent, matcher) => {
    const props = getMockCallProps(mockComponent).filter(matcher);
    if (props.length === 0) {
        throw new Error('Expected matching mock component to be called');
    }

    return props[props.length - 1];
};

const getSelectionStore = () => {
    const stores = MultiSelectionStoreMock.mock.instances;
    if (stores.length === 0) {
        throw new Error('Expected MultiSelectionStore to be instantiated');
    }

    return stores[stores.length - 1];
};

const getMultiItemSelectionProps = () => getLastMockCallProps(MultiItemSelectionMock);
const getMultiListOverlayProps = () => getLastMockCallProps(MultiListOverlayMock);
const getMultiListOverlayInstance = () => {
    const listStores = MultiListOverlayMock.__listStores;
    if (!listStores || listStores.length === 0) {
        throw new Error('Expected MultiListOverlay listStore to be instantiated');
    }

    return {listStore: listStores[listStores.length - 1]};
};

const getMultiItemPropsById = (id) => getLastMockCallPropsMatching(
    MultiItemSelectionItemMock,
    (props) => props.id === id
);

const renderMultiSelection = (customProps: Object = {}) => {
    let props = {
        adapter: 'table',
        listKey: 'snippets',
        onChange: jest.fn(),
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        ...customProps,
    };

    const selectionRef = React.createRef();
    const view = render(<MultiSelection {...props} ref={selectionRef} />);

    return {
        ...view,
        selectionRef,
        setProps: (newProps: Object) => {
            props = {...props, ...newProps};
            view.rerender(<MultiSelection {...props} ref={selectionRef} />);
        },
    };
};

beforeEach(() => {
    const body = document.body;

    if (body) {
        body.innerHTML = '';
    }

    jest.clearAllMocks();
});

test('Show with passed icon and label and open overlay', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2'}, {id: 5, title: 'Title 5'}];
        this.loadItems = jest.fn();
    });

    const selection = renderMultiSelection({
        displayProperties: ['id', 'title'],
        icon: 'su-document',
        label: 'Select Snippets',
    });

    await user.click(screen.getByRole('button', {name: 'su-document'}));

    expect(getMultiListOverlayProps().open).toEqual(true);
    expect(selection.asFragment()).toMatchSnapshot();
});

test('Pass correct props to MultiItemSelection component', () => {
    renderMultiSelection({
        disabled: true,
        resourceKey: 'snippets',
        sortable: false,
    });

    expect(getMultiItemSelectionProps().disabled).toEqual(true);
    expect(getMultiItemSelectionProps().sortable).toEqual(false);
});

test('Render with disabled item', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {id: 1},
            {id: 2},
        ];
    });

    renderMultiSelection({
        disabledIds: [2, 4],
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    expect(getMultiItemPropsById(1).disabled).toEqual(false);
    expect(getMultiItemPropsById(2).disabled).toEqual(true);
});

test('Pass locale to MultiListOverlay', () => {
    const locale = observable.box('de');
    renderMultiSelection({locale});

    expect(getMultiListOverlayProps().locale.get()).toEqual('de');
});

test('Pass options to MultiListOverlay', () => {
    renderMultiSelection({options: {types: 'test'}});

    expect(getMultiListOverlayProps().options).toEqual({types: 'test'});
});

test('Pass disabledIds to MultiListOverlay', () => {
    const disabledIds = [1, 2, 4];
    renderMultiSelection({disabledIds});

    expect(getMultiListOverlayProps().disabledIds).toEqual(disabledIds);
});

test('Pass itemDisabledCondition to MultiListOverlay', () => {
    renderMultiSelection({itemDisabledCondition: 'status == "inactive"'});

    expect(getMultiListOverlayProps().itemDisabledCondition).toEqual('status == "inactive"');
});

test('Construct MultiSelectionStore with correct parameters', () => {
    const locale = observable.box('en');

    renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [1, 2, 5],
    });

    expect(MultiSelectionStore).toBeCalledWith('snippets', [1, 2, 5], locale, 'ids', {key: 'value-1'});
});

test('Update requestParameters and reload items of MultiSelectionStore when options prop is changed', () => {
    const locale = observable.box('en');

    const selection = renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [1, 2, 5],
    });

    const selectionStore = getSelectionStore();
    expect(selectionStore.setRequestParameters).not.toBeCalled();
    expect(selectionStore.loadItems).not.toBeCalled();

    selection.setProps({options: {key: 'value-2'}});

    expect(selectionStore.setRequestParameters).toBeCalledWith({key: 'value-2'});
    expect(selectionStore.loadItems).toBeCalledWith([1, 2, 5]);
});

test('Not reload items of MultiSelectionStore when new value of option prop is equal to old value', () => {
    const locale = observable.box('en');

    const selection = renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [],
    });

    const selectionStore = getSelectionStore();
    expect(selectionStore.setRequestParameters).not.toBeCalled();
    expect(selectionStore.loadItems).not.toBeCalled();

    selection.setProps({options: {key: 'value-1'}});

    expect(selectionStore.setRequestParameters).not.toBeCalled();
    expect(selectionStore.loadItems).not.toBeCalled();
});

test('Should not open an overlay on icon-click when disabled', async() => {
    const user = userEvent.setup();

    renderMultiSelection({disabled: true});

    await user.click(screen.getByRole('button', {name: 'su-plus'}));
    expect(getMultiListOverlayProps().open).toEqual(false);
});

test('Should close an overlay using the close button', async() => {
    const user = userEvent.setup();

    renderMultiSelection();

    await user.click(screen.getByRole('button', {name: 'su-plus'}));
    expect(getMultiListOverlayProps().open).toEqual(true);

    act(() => {
        getMultiListOverlayProps().onClose();
    });
    expect(getMultiListOverlayProps().open).toEqual(false);
});

test('Should close an overlay using the confirm button', async() => {
    const user = userEvent.setup();

    renderMultiSelection();

    await user.click(screen.getByRole('button', {name: 'su-plus'}));
    expect(getMultiListOverlayProps().open).toEqual(true);

    act(() => {
        getMultiListOverlayProps().onConfirm([1]);
    });
    expect(getMultiListOverlayProps().open).toEqual(false);
});

test('Should call the onChange callback when clicking the confirm button', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    renderMultiSelection({onChange: changeSpy});

    await user.click(screen.getByRole('button', {name: 'su-plus'}));
    act(() => {
        getMultiListOverlayProps().onConfirm([3, 7, 2]);
    });

    expect(getSelectionStore().set).toBeCalledWith([3, 7, 2]);
});

test('Should not call the onChange callback when items have not changed', () => {
    const changeSpy = jest.fn();
    const selection = renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    expect(changeSpy).not.toBeCalled();

    act(() => {
        getSelectionStore().items = [{id: 1}];
    });
    selection.setProps({value: [1]});

    expect(changeSpy).not.toBeCalled();
});

test('Should call the onItemClick callback when an item was clicked', () => {
    const itemClickSpy = jest.fn();
    const item1 = {id: 1, title: 'Title 1'};
    const item2 = {id: 2, title: 'Title 2'};

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [item1, item2];
        this.loadItems = jest.fn();
    });

    renderMultiSelection({
        displayProperties: ['id', 'title'],
        icon: 'su-document',
        label: 'Select Snippets',
        onItemClick: itemClickSpy,
    });

    getMultiItemSelectionProps().onItemClick(1, item1);
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, item1);
    getMultiItemSelectionProps().onItemClick(2, item2);
    expect(itemClickSpy).toHaveBeenLastCalledWith(2, item2);
});

test('Should load the items if value prop changes', () => {
    const selection = renderMultiSelection({value: [1]});

    selection.setProps({value: [1, 3]});
    expect(getSelectionStore().loadItems).toBeCalledWith([1, 3]);
});

test('Should instantiate the ListStore with the correct resourceKey and destroy it on unmount', () => {
    const selection = renderMultiSelection({
        listKey: 'pages_list',
        resourceKey: 'pages',
    });

    const listStore = getMultiListOverlayInstance().listStore;
    expect(listStore.listKey).toEqual('pages_list');
    expect(listStore.resourceKey).toEqual('pages');

    selection.unmount();
    expect(listStore.destroy).toBeCalled();
});

test('Should instantiate the ListStore with the preselected ids', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
    });

    renderMultiSelection({
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    const listStore = getMultiListOverlayInstance().listStore;
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).toBeCalledWith({id: 5});
    expect(listStore.select).toBeCalledWith({id: 8});
});

test('Should reinstantiate the ListStore with the preselected ids when new props are received', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    const listStore = getMultiListOverlayInstance().listStore;
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).toBeCalledWith({id: 5});
    expect(listStore.select).toBeCalledWith({id: 8});

    selection.setProps({value: [1, 3]});
    const loadItemsCall = getSelectionStore().loadItems.mock.calls[0];
    expect(loadItemsCall[0]).toEqual([1, 3]);
});

test('Should not reload items if none of the items changed', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    const listStore = getMultiListOverlayInstance().listStore;
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).toBeCalledWith({id: 5});
    expect(listStore.select).toBeCalledWith({id: 8});

    selection.setProps({value: [1, 5, 8]});
    expect(getSelectionStore().loadItems).not.toBeCalled();
});

test('Should not reinstantiate the ListStore with the preselected ids when new props have the same values', () => {
    const locale = observable.box('en');

    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}, {id: 5}, {id: 8}];
        this.loadItems = jest.fn();
    });

    const selection = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    selection.setProps({value: [1, 5, 8]});
    expect(getSelectionStore().loadItems).not.toBeCalled();
});

test('Should remove an item when the remove button is clicked', () => {
    renderMultiSelection({value: [3, 7, 9]});

    getMultiItemSelectionProps().onItemRemove(7);
    expect(getSelectionStore().removeById).toBeCalledWith(7);
});

test('Should reorder the items on drag and drop', () => {
    renderMultiSelection({value: [3, 7, 9]});

    getMultiItemSelectionProps().onItemsSorted(1, 2);
    expect(getSelectionStore().move).toBeCalledWith(1, 2);
});

test('Should call the onChange callback if the value of the selection-store changes', () => {
    const changeSpy = jest.fn();
    renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    act(() => {
        getSelectionStore().items = [{id: 22}, {id: 23}];
    });
    expect(changeSpy).toBeCalledWith([22, 23]);
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();
    const selection = renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    selection.setProps({overlayTitle: 'New Selection Title'});
    expect(changeSpy).not.toBeCalled();
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    act(() => {
        getSelectionStore().items = [{id: 22}, {id: 23}];
    });
    expect(changeSpy).toBeCalledWith([22, 23]);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should render selected item in disabled state if it fulfills passed itemDisabledCondition', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {id: 1, status: 'active'},
            {id: 2, status: 'inactive'},
        ];
    });

    renderMultiSelection({
        itemDisabledCondition: 'status == "inactive"',
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    expect(getMultiItemPropsById(1).disabled).toEqual(false);
    expect(getMultiItemPropsById(2).disabled).toEqual(true);
});

test('Should render selected item in disabled state if passed disabledIds contain id of item', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {id: 1},
            {id: 2},
        ];
    });

    renderMultiSelection({
        disabledIds: [2, 4],
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    expect(getMultiItemPropsById(1).disabled).toEqual(false);
    expect(getMultiItemPropsById(2).disabled).toEqual(true);
});

test('Pass correct allowRemoveWhileDisabled prop to Item of MultiItemSelection', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [{id: 1}];
    });

    renderMultiSelection({
        allowDeselectForDisabledItems: true,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    expect(getMultiItemPropsById(1).allowRemoveWhileDisabled).toEqual(true);
});

test('PublishIndicator should be rendered if necessary', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                published: '2020-11-16',
                publishedState: true,
            },
            {
                id: 2,
                published: '2020-11-16',
                publishedState: false,
            },
            {
                id: 3,
                published: null,
                publishedState: false,
            },
        ];
    });

    renderMultiSelection({
        listKey: 'pages',
        resourceKey: 'pages',
        value: [1, 2, 3],
    });

    expect(PublishIndicatorMock).toHaveBeenCalledTimes(2);
    expect(PublishIndicatorMock).toBeCalledWith(
        expect.objectContaining({draft: true, published: true}),
        expect.anything()
    );
    expect(PublishIndicatorMock).toBeCalledWith(
        expect.objectContaining({draft: true, published: false}),
        expect.anything()
    );
});
