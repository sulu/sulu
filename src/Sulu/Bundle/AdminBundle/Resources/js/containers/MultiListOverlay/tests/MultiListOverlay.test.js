// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlayComponent from '../../../containers/ListOverlay';
import MultiListOverlay from '../MultiListOverlay';

const ExampleList = function ExampleList(props) {
    return <div className={props.adapter ? props.className : null} />;
};

jest.mock('../../../containers/ListOverlay', () => jest.fn(function ListOverlay(props) {
    return <ExampleList adapter={props.adapter} className="list" />;
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.clear = jest.fn();
        this.reset = jest.fn();
        this.selections = [];
        this.selectionIds = [];
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

function renderMultiListOverlay(props = {}) {
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
        <MultiListOverlay {...(mergedProps: any)} />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should instantiate the ListStore with locale, excluded-ids and options', () => {
    const locale = observable.box('en');
    const options = {};

    renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale,
        options,
    });

    const listStore = getListStoreMock();

    expect(listStore.listKey).toEqual('snippets_list');
    expect(listStore.resourceKey).toEqual('snippets');
    expect(listStore.observableOptions.locale.get()).toEqual('en');
    expect(listStore.observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(listStore.options).toBe(options);
});

test('Should update options of ListStore if the options prop is changed', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderMultiListOverlay({
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
        <MultiListOverlay
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

    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });
    const listStore = getListStoreMock();

    expect(listStore.reset).not.toBeCalled();

    const newOldOptions = {key: 'value-1'};
    rerender(
        <MultiListOverlay
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

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
    });
    const listStore = getListStoreMock();

    expect(listStore.clear).not.toBeCalled();

    rerender(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-3']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(listStore.clear).toBeCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
    });
    const listStore = getListStoreMock();

    expect(listStore.clear).not.toBeCalled();

    rerender(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(listStore.clear).not.toBeCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids and options', () => {
    renderMultiListOverlay();
    const listStore = getListStoreMock();

    expect(listStore.observableOptions.locale).toEqual(undefined);
    expect(listStore.observableOptions.excludedIds.get()).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    renderMultiListOverlay({
        allowActivateForDisabledItems: true,
    });

    expect(getLatestListOverlayProps().overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    renderMultiListOverlay({
        allowActivateForDisabledItems: true,
        overlayType: 'dialog',
    });

    expect(getLatestListOverlayProps().overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderMultiListOverlay({
        allowActivateForDisabledItems: true,
        disabledIds,
    });

    expect(getLatestListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLatestListOverlayProps().allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    renderMultiListOverlay({
        allowActivateForDisabledItems: false,
        reloadOnOpen: true,
    });

    expect(getLatestListOverlayProps().reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    renderMultiListOverlay({
        allowActivateForDisabledItems: false,
        clearSelectionOnClose: true,
    });

    expect(getLatestListOverlayProps().clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderMultiListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(getLatestListOverlayProps().disabledIds).toBe(disabledIds);
    expect(getLatestListOverlayProps().allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    renderMultiListOverlay({
        itemDisabledCondition: 'status == "inactive"',
    });

    expect(getLatestListOverlayProps().itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the ListOverlay', () => {
    renderMultiListOverlay({
        confirmLoading: true,
        open: true,
        preSelectedItems: [{}],
        title: 'Test',
    });

    expect(getLatestListOverlayProps().confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    renderMultiListOverlay({
        onConfirm: confirmSpy,
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    const listStore = getListStoreMock();
    listStore.selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).not.toBeCalled();
    getLatestListOverlayProps().onConfirm();

    expect(confirmSpy).toBeCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the ListStore', () => {
    renderMultiListOverlay({
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    expect(ListStore).toBeCalledWith(
        'snippets',
        'snippets',
        'multi_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        [1, 2, 3]
    );
});

test('Should not add the preSelectedItems to the ListStore if preloadSelectedItems is set to false', () => {
    renderMultiListOverlay({
        open: true,
        preloadSelectedItems: false,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    expect(ListStore).toBeCalledWith(
        'snippets',
        'snippets',
        'multi_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        undefined
    );
});

test('Should not fail when preSelectedItems is undefined', () => {
    renderMultiListOverlay({
        open: true,
    });
    const listStore = getListStoreMock();

    expect(listStore.select).not.toBeCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    renderMultiListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });
    expect((ListOverlayComponent: any).mock.calls[0][0].adapter).toEqual('table');

    renderMultiListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });
    expect((ListOverlayComponent: any).mock.calls[1][0].adapter).toEqual('column_list');
});
