// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {autorun, extendObservable as mockExtendObservable, observable} from 'mobx';
import ListStore from '../../../containers/List/stores/ListStore';
import SingleListOverlay from '../SingleListOverlay';

let mockListOverlayProps: Object = {};
let mockListStoreInstances: Array<Object> = [];

const mockReact = require('react');

jest.mock('mobx', () => {
    const actualMobx = jest.requireActual('mobx');

    return {
        ...actualMobx,
        autorun: jest.fn(actualMobx.autorun),
    };
});

jest.mock('../../../utils/Translator');

jest.mock('../../../containers/ListOverlay', () => jest.fn((props) => {
    mockListOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-adapter': props.adapter,
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'list-overlay',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'confirm',
                onClick: props.onConfirm,
                type: 'button',
            },
            'Confirm'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'close',
                onClick: props.onClose,
                type: 'button',
            },
            'Close'
        )
    );
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions, initialSelectionIds) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.observableOptions = observableOptions;
        this.initialSelectionIds = initialSelectionIds;
        this.selectionIds = [];
        this.select = jest.fn();
        this.clear = jest.fn();
        this.reset = jest.fn();
        this.clearSelection = jest.fn();
        this.destroy = jest.fn();

        mockExtendObservable(this, {
            selections: [],
        });

        mockListStoreInstances.push(this);
    }
));

beforeEach(() => {
    jest.clearAllMocks();
    mockListOverlayProps = {};
    mockListStoreInstances = [];
    (autorun: any).mockImplementation(jest.requireActual('mobx').autorun);
});

function renderSingleListOverlay(props: Object = {}) {
    return render(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
            {...props}
        />
    );
}

function getListStore() {
    return mockListStoreInstances[0];
}

test('Should instantiate the ListStore with locale, excluded-ids, options and metadataOptions', () => {
    const locale = observable.box('en');
    const options = {};
    const metadataOptions = {id: 2};

    renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale,
        metadataOptions,
        options,
    });

    expect(getListStore().listKey).toEqual('snippets_list');
    expect(getListStore().resourceKey).toEqual('snippets');
    expect(getListStore().observableOptions.locale.get()).toEqual('en');
    expect(getListStore().observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(getListStore().options).toBe(options);
    expect(getListStore().metadataOptions).toBe(metadataOptions);
});

test('Should update options of ListStore if the options prop is changed', async() => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });
    getListStore().selectionIds = [12, 14];

    expect(getListStore().reset).not.toHaveBeenCalled();
    expect(getListStore().options).toEqual(oldOptions);

    const newOptions = {key: 'value-2'};
    rerender(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={newOptions}
            resourceKey="snippets"
            title="Selection"
        />
    );

    await waitFor(() => expect(getListStore().reset).toHaveBeenCalled());
    expect(getListStore().initialSelectionIds).toEqual([12, 14]);
    expect(getListStore().options).toEqual(newOptions);
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });

    expect(getListStore().reset).not.toHaveBeenCalled();

    rerender(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={{key: 'value-1'}}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(getListStore().reset).not.toHaveBeenCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids, options and metadataOptions', () => {
    renderSingleListOverlay();

    expect(getListStore().observableOptions.locale).toEqual(undefined);
    expect(getListStore().observableOptions.excludedIds.get()).toEqual(undefined);
    expect(getListStore().options).toEqual(undefined);
    expect(getListStore().metadataOptions).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    renderSingleListOverlay({allowActivateForDisabledItems: true});

    expect(mockListOverlayProps.overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        overlayType: 'dialog',
    });

    expect(mockListOverlayProps.overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        disabledIds,
    });

    expect(mockListOverlayProps.disabledIds).toBe(disabledIds);
    expect(mockListOverlayProps.allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    renderSingleListOverlay({reloadOnOpen: true});

    expect(mockListOverlayProps.reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    renderSingleListOverlay({clearSelectionOnClose: true});

    expect(mockListOverlayProps.clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(mockListOverlayProps.disabledIds).toBe(disabledIds);
    expect(mockListOverlayProps.allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    renderSingleListOverlay({itemDisabledCondition: 'status == "inactive"'});

    expect(mockListOverlayProps.itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the Overlay', () => {
    renderSingleListOverlay({
        confirmLoading: true,
        open: true,
        title: 'Test',
    });

    expect(mockListOverlayProps.confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', async() => {
    const confirmSpy = jest.fn();

    renderSingleListOverlay({
        onConfirm: confirmSpy,
        open: true,
    });

    act(() => {
        getListStore().selections = [{id: 1}];
    });

    expect(confirmSpy).not.toHaveBeenCalled();
    await userEvent.click(screen.getByLabelText('confirm'));

    expect(confirmSpy).toHaveBeenCalledWith({id: 1});
});

test('Should pass the id from the preSelectedItems to the ListStore', () => {
    renderSingleListOverlay({
        metadataOptions: undefined,
        open: true,
        preSelectedItem: {id: 1},
    });

    expect(ListStore).toHaveBeenCalledWith(
        'snippets',
        'snippets',
        'single_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        [1]
    );
});

test('Should not fail when preSelectedItem is undefined', () => {
    renderSingleListOverlay({open: true});

    expect(getListStore().select).not.toHaveBeenCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    const {unmount} = renderSingleListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });

    expect(mockListOverlayProps.adapter).toEqual('table');
    unmount();

    renderSingleListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });

    expect(mockListOverlayProps.adapter).toEqual('column_list');
});

test('Should only select a single item at a time', async() => {
    renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });

    act(() => {
        getListStore().selections.push({id: 3});
    });

    expect(getListStore().selections.slice()).toEqual([{id: 3}]);

    act(() => {
        getListStore().selections.push({id: 5});
    });

    await waitFor(() => expect(getListStore().clearSelection).toHaveBeenCalledWith());
    expect(getListStore().select).toHaveBeenCalledWith({id: 5});
});

test('Should clear ListStore if the excludedIds prop is changed', async() => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });

    expect(getListStore().clear).not.toHaveBeenCalled();

    rerender(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-3']}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );

    await waitFor(() => expect(getListStore().clear).toHaveBeenCalled());
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });

    expect(getListStore().clear).not.toHaveBeenCalled();

    rerender(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );

    expect(getListStore().clear).not.toHaveBeenCalled();
});

test('Should destroy listStore and autorun when unmounted', () => {
    const selectionDisposerSpy = jest.fn();
    (autorun: any).mockImplementation(() => selectionDisposerSpy);

    const {unmount} = renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });

    const listStore = getListStore();
    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(selectionDisposerSpy).toHaveBeenCalledWith();
});
