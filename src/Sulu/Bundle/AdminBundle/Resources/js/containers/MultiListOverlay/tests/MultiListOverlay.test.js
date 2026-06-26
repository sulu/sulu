// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ListStore from '../../../containers/List/stores/ListStore';
import MultiListOverlay from '../MultiListOverlay';

let mockListOverlayProps: Object = {};
let mockListStoreInstances: Array<Object> = [];

const mockReact = require('react');

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
        this.select = jest.fn();
        this.clear = jest.fn();
        this.reset = jest.fn();
        this.destroy = jest.fn();
        this.selections = [];
        this.selectionIds = [];

        mockListStoreInstances.push(this);
    }
));

beforeEach(() => {
    jest.clearAllMocks();
    mockListOverlayProps = {};
    mockListStoreInstances = [];
});

function renderMultiListOverlay(props: Object = {}) {
    return render(
        <MultiListOverlay
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

test('Should instantiate the ListStore with locale, excluded-ids and options', () => {
    const locale = observable.box('en');
    const options = {};

    renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale,
        options,
    });

    expect(getListStore().listKey).toEqual('snippets_list');
    expect(getListStore().resourceKey).toEqual('snippets');
    expect(getListStore().observableOptions.locale.get()).toEqual('en');
    expect(getListStore().observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(getListStore().options).toBe(options);
});

test('Should update options of ListStore if the options prop is changed', async() => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });
    getListStore().selectionIds = [12, 14];

    expect(getListStore().reset).not.toHaveBeenCalled();
    expect(getListStore().options).toEqual(oldOptions);

    rerender(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={{key: 'value-2'}}
            resourceKey="snippets"
            title="Selection"
        />
    );

    await waitFor(() => expect(getListStore().reset).toHaveBeenCalled());
    expect(getListStore().initialSelectionIds).toEqual([12, 14]);
    expect(getListStore().options).toEqual({key: 'value-2'});
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
        options: oldOptions,
    });

    expect(getListStore().reset).not.toHaveBeenCalled();

    rerender(
        <MultiListOverlay
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

test('Should clear ListStore if the excludedIds prop is changed', async() => {
    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
    });

    expect(getListStore().clear).not.toHaveBeenCalled();

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

    await waitFor(() => expect(getListStore().clear).toHaveBeenCalled());
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = renderMultiListOverlay({
        excludedIds: ['id-1', 'id-2'],
        listKey: 'snippets_list',
        locale: observable.box('en'),
    });

    expect(getListStore().clear).not.toHaveBeenCalled();

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

    expect(getListStore().clear).not.toHaveBeenCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids and options', () => {
    renderMultiListOverlay();

    expect(getListStore().observableOptions.locale).toEqual(undefined);
    expect(getListStore().observableOptions.excludedIds.get()).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    renderMultiListOverlay({allowActivateForDisabledItems: true});

    expect(mockListOverlayProps.overlayType).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    renderMultiListOverlay({
        allowActivateForDisabledItems: true,
        overlayType: 'dialog',
    });

    expect(mockListOverlayProps.overlayType).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderMultiListOverlay({
        allowActivateForDisabledItems: true,
        disabledIds,
    });

    expect(mockListOverlayProps.disabledIds).toBe(disabledIds);
    expect(mockListOverlayProps.allowActivateForDisabledItems).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    renderMultiListOverlay({reloadOnOpen: true});

    expect(mockListOverlayProps.reloadOnOpen).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    renderMultiListOverlay({clearSelectionOnClose: true});

    expect(mockListOverlayProps.clearSelectionOnClose).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    renderMultiListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(mockListOverlayProps.disabledIds).toBe(disabledIds);
    expect(mockListOverlayProps.allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    renderMultiListOverlay({itemDisabledCondition: 'status == "inactive"'});

    expect(mockListOverlayProps.itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the ListOverlay', () => {
    renderMultiListOverlay({
        confirmLoading: true,
        open: true,
        preSelectedItems: [{}],
        title: 'Test',
    });

    expect(mockListOverlayProps.confirmLoading).toEqual(true);
});

test('Should call onConfirm with the current selection', async() => {
    const confirmSpy = jest.fn();

    renderMultiListOverlay({
        onConfirm: confirmSpy,
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    getListStore().selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).not.toHaveBeenCalled();
    await userEvent.click(screen.getByLabelText('confirm'));

    expect(confirmSpy).toHaveBeenCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the ListStore', () => {
    renderMultiListOverlay({
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    expect(ListStore).toHaveBeenCalledWith(
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

    expect(ListStore).toHaveBeenCalledWith(
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
    renderMultiListOverlay({open: true});

    expect(getListStore().select).not.toHaveBeenCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    const {unmount} = renderMultiListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });

    expect(mockListOverlayProps.adapter).toEqual('table');
    unmount();

    renderMultiListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });

    expect(mockListOverlayProps.adapter).toEqual('column_list');
});
