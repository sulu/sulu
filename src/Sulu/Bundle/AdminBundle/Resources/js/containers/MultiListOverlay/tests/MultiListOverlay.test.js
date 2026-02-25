// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render} from '@testing-library/react';
import MultiListOverlay from '../MultiListOverlay';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/ListOverlay', () => jest.fn(() => <div data-testid="list-overlay" />));

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
        this.destroy = jest.fn();
        this.selections = [];
        this.selectionIds = [];
    }
));

const ListOverlay = jest.requireMock('../../../containers/ListOverlay');
const ListStoreMock: any = jest.requireMock('../../../containers/List/stores/ListStore');

type MultiListOverlayProps = React$ElementConfig<typeof MultiListOverlay>;

const createProps = (overrides: Object = {}): MultiListOverlayProps => ({
    adapter: 'table',
    clearSelectionOnClose: false,
    disabledIds: [],
    excludedIds: [],
    listKey: 'snippets',
    onClose: jest.fn(),
    onConfirm: jest.fn(),
    open: false,
    overlayType: 'overlay',
    preSelectedItems: [],
    preloadSelectedItems: true,
    resourceKey: 'snippets',
    title: 'Selection',
    ...(overrides: any),
}: any);

const getListStore = () => ListStoreMock.mock.instances[ListStoreMock.mock.instances.length - 1];

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should instantiate the ListStore with locale, excluded-ids and options', () => {
    const locale = observable.box('en');
    const options = {};

    render(
        <MultiListOverlay
            {...createProps({
                excludedIds: ['id-1', 'id-2'],
                listKey: 'snippets_list',
                locale,
                options,
            })}
        />
    );

    const listStore = getListStore();
    expect(listStore.listKey).toEqual('snippets_list');
    expect(listStore.resourceKey).toEqual('snippets');
    expect(listStore.observableOptions.locale.get()).toEqual('en');
    expect(listStore.observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(listStore.options).toBe(options);
});

test('Should update options of ListStore if the options prop is changed', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = render(
        <MultiListOverlay
            {...createProps({
                excludedIds: ['id-1', 'id-2'],
                listKey: 'snippets_list',
                locale: observable.box('en'),
                options: oldOptions,
            })}
        />
    );
    const listStore = getListStore();
    listStore.selectionIds = [12, 14];

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.options).toEqual(oldOptions);

    const newOptions = {key: 'value-2'};
    act(() => {
        rerender(<MultiListOverlay {...createProps({
            excludedIds: ['id-1', 'id-2'],
            listKey: 'snippets_list',
            locale: observable.box('en'),
            options: newOptions,
        })}
        />);
    });

    expect(listStore.reset).toBeCalled();
    expect(listStore.initialSelectionIds).toEqual([12, 14]);
    expect(listStore.options).toEqual(newOptions);
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = render(
        <MultiListOverlay
            {...createProps({
                excludedIds: ['id-1', 'id-2'],
                listKey: 'snippets_list',
                locale: observable.box('en'),
                options: oldOptions,
            })}
        />
    );

    const listStore = getListStore();
    expect(listStore.reset).not.toBeCalled();

    const newOldOptions = {key: 'value-1'};
    act(() => {
        rerender(<MultiListOverlay {...createProps({
            excludedIds: ['id-1', 'id-2'],
            listKey: 'snippets_list',
            locale: observable.box('en'),
            options: newOldOptions,
        })}
        />);
    });

    expect(listStore.reset).not.toBeCalled();
});

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const {rerender} = render(
        <MultiListOverlay
            {...createProps({
                excludedIds: ['id-1', 'id-2'],
                listKey: 'snippets_list',
                locale: observable.box('en'),
            })}
        />
    );

    const listStore = getListStore();
    expect(listStore.clear).not.toBeCalled();

    act(() => {
        rerender(<MultiListOverlay {...createProps({
            excludedIds: ['id-3'],
            listKey: 'snippets_list',
            locale: observable.box('en'),
        })}
        />);
    });

    expect(listStore.clear).toBeCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = render(
        <MultiListOverlay
            {...createProps({
                excludedIds: ['id-1', 'id-2'],
                listKey: 'snippets_list',
                locale: observable.box('en'),
            })}
        />
    );

    const listStore = getListStore();
    expect(listStore.clear).not.toBeCalled();

    act(() => {
        rerender(<MultiListOverlay {...createProps({
            excludedIds: ['id-1', 'id-2'],
            listKey: 'snippets_list',
            locale: observable.box('en'),
        })}
        />);
    });

    expect(listStore.clear).not.toBeCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids and options', () => {
    render(<MultiListOverlay {...createProps()} />);

    const listStore = getListStore();
    expect(listStore.observableOptions.locale).toEqual(undefined);
    expect(listStore.observableOptions.excludedIds.get()).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    render(<MultiListOverlay {...createProps({allowActivateForDisabledItems: true})} />);

    expect(getLatestMockProps(ListOverlay).overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    render(<MultiListOverlay {...createProps({allowActivateForDisabledItems: true, overlayType: 'dialog'})} />);

    expect(getLatestMockProps(ListOverlay).overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    render(
        <MultiListOverlay
            {...createProps({
                allowActivateForDisabledItems: true,
                disabledIds,
            })}
        />
    );

    expect(getLatestMockProps(ListOverlay).disabledIds).toBe(disabledIds);
    expect(getLatestMockProps(ListOverlay).allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    render(<MultiListOverlay {...createProps({allowActivateForDisabledItems: false, reloadOnOpen: true})} />);

    expect(getLatestMockProps(ListOverlay).reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    render(<MultiListOverlay {...createProps({allowActivateForDisabledItems: false, clearSelectionOnClose: true})} />);

    expect(getLatestMockProps(ListOverlay).clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    render(
        <MultiListOverlay
            {...createProps({
                allowActivateForDisabledItems: false,
                disabledIds,
            })}
        />
    );

    expect(getLatestMockProps(ListOverlay).disabledIds).toBe(disabledIds);
    expect(getLatestMockProps(ListOverlay).allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    render(<MultiListOverlay {...createProps({itemDisabledCondition: 'status == "inactive"'})} />);

    expect(getLatestMockProps(ListOverlay).itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the ListOverlay', () => {
    render(
        <MultiListOverlay
            {...createProps({
                confirmLoading: true,
                open: true,
                preSelectedItems: [{}],
                title: 'Test',
            })}
        />
    );

    expect(getLatestMockProps(ListOverlay).confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    render(
        <MultiListOverlay
            {...createProps({
                onConfirm: confirmSpy,
                open: true,
                preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
            })}
        />
    );

    const listStore = getListStore();
    listStore.selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).not.toBeCalled();
    getLatestMockProps(ListOverlay).onConfirm();

    expect(confirmSpy).toBeCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the ListStore', () => {
    render(<MultiListOverlay {...createProps({
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    })}
    />);

    expect(ListStoreMock).toBeCalledWith(
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
    render(
        <MultiListOverlay
            {...createProps({
                open: true,
                preloadSelectedItems: false,
                preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
            })}
        />
    );

    expect(ListStoreMock).toBeCalledWith(
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
    render(<MultiListOverlay {...createProps({open: true})} />);

    const listStore = getListStore();

    expect(listStore.select).not.toBeCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    render(<MultiListOverlay {...createProps({adapter: 'table', open: true, title: 'test'})} />);
    expect(getLatestMockProps(ListOverlay).adapter).toEqual('table');

    render(<MultiListOverlay {...createProps({adapter: 'column_list', open: true, title: 'test'})} />);
    expect(getLatestMockProps(ListOverlay).adapter).toEqual('column_list');
});
