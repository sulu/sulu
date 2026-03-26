// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render} from '@testing-library/react';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlayComponent from '../../../containers/ListOverlay';
import SingleListOverlay from '../SingleListOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const ExampleList = function ExampleList(props) {
    return <div className={props.adapter ? props.className : null} />;
};

jest.mock('../../../containers/ListOverlay', () => jest.fn(function ListOverlay(props) {
    return <ExampleList adapter={props.adapter} className="list" />;
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.observableOptions = observableOptions;
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

function getListStoreMock() {
    return (ListStore: any).mock.instances[0];
}

function getLatestListOverlayProps() {
    const calls = (ListOverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function renderSingleListOverlay(props = {}) {
    const mergedProps = {
        adapter: 'table',
        listKey: 'snippets',
        onClose: jest.fn(),
        onConfirm: jest.fn(),
        open: false,
        resourceKey: 'snippets',
        title: 'Selection',
        ...props,
    };

    return render(
        <SingleListOverlay {...(mergedProps: any)} />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

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
    const listStore = getListStoreMock();

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
    const listStore = getListStoreMock();
    listStore.selectionIds = [12, 14];

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.options).toEqual(oldOptions);

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

    expect(listStore.reset).toBeCalled();
    expect(listStore.initialSelectionIds).toEqual([12, 14]);
    expect(listStore.options).toEqual(newOptions);
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });
    const listStore = getListStoreMock();

    expect(listStore.reset).not.toBeCalled();

    const newOldOptions = {key: 'value-1'};
    rerender(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={newOldOptions}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(listStore.reset).not.toBeCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids, options and metadataOptions', () => {
    renderSingleListOverlay();
    const listStore = getListStoreMock();

    expect(listStore.observableOptions.locale).toEqual(undefined);
    expect(listStore.observableOptions.excludedIds.get()).toEqual(undefined);
    expect(listStore.options).toEqual(undefined);
    expect(listStore.metadataOptions).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
    });

    expect(getLatestListOverlayProps().overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        overlayType: 'dialog',
    });

    expect(getLatestListOverlayProps().overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: true,
        disabledIds,
    });

    expect(getLatestListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLatestListOverlayProps().allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        reloadOnOpen: true,
    });

    expect(getLatestListOverlayProps().reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        clearSelectionOnClose: true,
    });

    expect(getLatestListOverlayProps().clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderSingleListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(getLatestListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLatestListOverlayProps().allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    renderSingleListOverlay({
        itemDisabledCondition: 'status == "inactive"',
    });

    expect(getLatestListOverlayProps().itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the Overlay', () => {
    renderSingleListOverlay({
        confirmLoading: true,
        open: true,
        title: 'Test',
    });

    expect(getLatestListOverlayProps().confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    renderSingleListOverlay({
        onConfirm: confirmSpy,
        open: true,
    });
    const listStore = getListStoreMock();
    listStore.selections = [{id: 1}];

    expect(confirmSpy).not.toBeCalled();
    getLatestListOverlayProps().onConfirm();

    expect(confirmSpy).toBeCalledWith({id: 1});
});

test('Should pass the id from the preSelectedItems to the ListStore', () => {
    renderSingleListOverlay({
        metadataOptions: undefined,
        open: true,
        preSelectedItem: {id: 1},
    });

    expect(ListStore).toBeCalledWith(
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
    const listStore = getListStoreMock();

    expect(listStore.select).not.toBeCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    renderSingleListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });
    expect((ListOverlayComponent: any).mock.calls[0][0].adapter).toEqual('table');

    renderSingleListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });
    expect((ListOverlayComponent: any).mock.calls[1][0].adapter).toEqual('column_list');
});

test('Should only select a single item at a time', () => {
    renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });
    const listStore = getListStoreMock();

    listStore.selections.push({id: 3});
    expect(listStore.selections).toEqual([{id: 3}]);

    listStore.selections.push({id: 5});
    expect(listStore.clearSelection).toBeCalledWith();
    expect(listStore.select).toBeCalledWith({id: 5});
});

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });
    const listStore = getListStoreMock();

    expect(listStore.clear).not.toBeCalled();

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

    expect(listStore.clear).toBeCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = renderSingleListOverlay({
        excludedIds: ['id-1', 'id-2'],
        open: true,
        title: 'test',
    });
    const listStore = getListStoreMock();

    expect(listStore.clear).not.toBeCalled();

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

    expect(listStore.clear).not.toBeCalled();
});

test('Should destroy listStore and autorun when unmounted', () => {
    const selectionDisposerSpy = jest.fn();
    const mobx = require('mobx');
    const autorunSpy = jest.spyOn(mobx, 'autorun');
    autorunSpy.mockImplementationOnce((callback) => {
        callback();
        return selectionDisposerSpy;
    });

    const {unmount} = renderSingleListOverlay({
        open: true,
        preSelectedItem: {id: 5},
        title: 'test',
    });
    const listStore = getListStoreMock();
    unmount();

    autorunSpy.mockRestore();
    expect(listStore.destroy).toBeCalledWith();
    expect(selectionDisposerSpy).toBeCalledWith();
});
