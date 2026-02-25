// @flow
import * as mobx from 'mobx';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../../../containers/ListOverlay';
import SingleListOverlay from '../SingleListOverlay';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/ListOverlay', () => jest.fn(function ListOverlayMock(props) {
    return (
        <div data-testid="list-overlay">
            <button onClick={props.onConfirm} type="button">confirm-overlay</button>
        </div>
    );
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions, initialSelectionIds) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.initialSelectionIds = initialSelectionIds;
        this.observableOptions = observableOptions;
        this.selectionIds = [];
        this.select = jest.fn();

        mockExtendObservable(this, {
            selections: [],
        });

        this.clear = jest.fn();
        this.reset = jest.fn();
        this.clearSelection = jest.fn();
        this.destroy = jest.fn();
    }
));

const listOverlayMock = (ListOverlay: any);

function createSingleListOverlayElement(props: Object = {}) {
    return (
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

function renderSingleListOverlay(props: Object = {}) {
    return render(createSingleListOverlayElement(props));
}

function getLastListStoreInstance() {
    // $FlowFixMe
    return ListStore.mock.instances[ListStore.mock.instances.length - 1];
}

function getLastListOverlayProps() {
    return getLatestMockProps(listOverlayMock);
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

    const listStore = getLastListStoreInstance();

    expect(listStore.listKey).toEqual('snippets_list');
    expect(listStore.resourceKey).toEqual('snippets');
    expect(listStore.observableOptions.locale.get()).toEqual('en');
    expect(listStore.observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(listStore.options).toBe(options);
    expect(listStore.metadataOptions).toBe(metadataOptions);
});

test('Should update options of ListStore if the options prop is changed', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });

    const listStore = getLastListStoreInstance();
    listStore.selectionIds = [12, 14];

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.options).toEqual(oldOptions);

    rerender(createSingleListOverlayElement({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: {key: 'value-2'},
    }));

    expect(listStore.reset).toHaveBeenCalled();
    expect(listStore.initialSelectionIds).toEqual([12, 14]);
    expect(listStore.options).toEqual({key: 'value-2'});
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });

    const listStore = getLastListStoreInstance();

    expect(listStore.reset).not.toHaveBeenCalled();

    rerender(createSingleListOverlayElement({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: {key: 'value-1'},
    }));

    expect(listStore.reset).not.toHaveBeenCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids, options and metadataOptions', () => {
    renderSingleListOverlay();

    const listStore = getLastListStoreInstance();

    expect(listStore.observableOptions.locale).toEqual(undefined);
    expect(listStore.observableOptions.excludedIds.get()).toEqual(undefined);
    expect(listStore.options).toEqual(undefined);
    expect(listStore.metadataOptions).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
    });

    expect(getLastListOverlayProps().overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        overlayType: 'dialog',
    });

    expect(getLastListOverlayProps().overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        disabledIds,
    });

    expect(getLastListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLastListOverlayProps().allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        reloadOnOpen: true,
    });

    expect(getLastListOverlayProps().reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        clearSelectionOnClose: true,
    });

    expect(getLastListOverlayProps().clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(getLastListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLastListOverlayProps().allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    renderSingleListOverlay({
        itemDisabledCondition: 'status == "inactive"',
    });

    expect(getLastListOverlayProps().itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the Overlay', () => {
    renderSingleListOverlay({
        confirmLoading: true,
        open: true,
        title: 'Test',
    });

    expect(getLastListOverlayProps().confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', async() => {
    const confirmSpy = jest.fn();
    const user = userEvent.setup();

    renderSingleListOverlay({
        onConfirm: confirmSpy,
        open: true,
    });

    const listStore = getLastListStoreInstance();
    listStore.selections = [{id: 1}];

    expect(confirmSpy).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));

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
    renderSingleListOverlay({
        open: true,
    });

    const listStore = getLastListStoreInstance();

    expect(listStore.select).not.toHaveBeenCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    renderSingleListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });

    expect(getLastListOverlayProps().adapter).toEqual('table');

    renderSingleListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });

    expect(getLastListOverlayProps().adapter).toEqual('column_list');
});

test('Should only select a single item at a time', () => {
    renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });

    const listStore = getLastListStoreInstance();

    listStore.selections.push({id: 3});
    expect(listStore.selections).toEqual([{id: 3}]);

    listStore.selections.push({id: 5});
    expect(listStore.clearSelection).toHaveBeenCalledWith();
    expect(listStore.select).toHaveBeenCalledWith({id: 5});
});

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });

    const listStore = getLastListStoreInstance();

    expect(listStore.clear).not.toHaveBeenCalled();

    rerender(createSingleListOverlayElement({
        excludedIds: ['id-3'],
        open: true,
        title: 'test',
    }));

    expect(listStore.clear).toHaveBeenCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });

    const listStore = getLastListStoreInstance();

    expect(listStore.clear).not.toHaveBeenCalled();

    rerender(createSingleListOverlayElement({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    }));

    expect(listStore.clear).not.toHaveBeenCalled();
});

test('Should destroy listStore and autorun when unmounted', () => {
    const selectionDisposerSpy = jest.fn();
    const autorunSpy = jest.spyOn(mobx, 'autorun').mockImplementation((callback) => {
        callback();

        return selectionDisposerSpy;
    });

    const {unmount} = renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });

    const listStore = getLastListStoreInstance();

    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(selectionDisposerSpy).toHaveBeenCalledWith();

    autorunSpy.mockRestore();
});
