// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';

let mockListProps: Object = {};

const mockReact = require('react');

jest.mock('../../../utils/Translator');

jest.mock('../../../containers/List', () => jest.fn((props) => {
    mockListProps = props;

    return mockReact.createElement('div', {'data-testid': 'list'});
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options) {
        this.options = options;
        this.clearSelection = jest.fn();
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.setActive = jest.fn();
        this.selections = [];
        this.reset = jest.fn();
        this.reload = jest.fn();
        this.loading = false;
    }
));

beforeEach(() => {
    jest.clearAllMocks();
    mockListProps = {};
});

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

function createListStore() {
    return new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
}

function renderListOverlay(props: Object = {}) {
    const listStore = props.listStore || createListStore();

    return {
        listStore,
        ...render(
            <ListOverlay
                adapter="table"
                listStore={listStore}
                onClose={jest.fn()}
                onConfirm={jest.fn()}
                open={false}
                title="Selection"
                {...props}
            />
        ),
    };
}

function getConfirmButton() {
    return screen.getByRole('button', {name: 'sulu_admin.confirm'});
}

test('Should use an Overlay by default', () => {
    renderListOverlay({open: true});

    expect(screen.getByRole('heading', {name: 'Selection'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_admin.cancel'})).not.toBeInTheDocument();
});

test('Should use a dialog if overlayType is set to dialog', () => {
    renderListOverlay({
        open: true,
        overlayType: 'dialog',
    });

    expect(screen.getByText('Selection')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
});

test('Should pass disabledIds to the List', () => {
    const disabledIds = [1, 2, 5];

    renderListOverlay({disabledIds, open: true});

    expect(mockListProps.disabledIds).toBe(disabledIds);
    expect(mockListProps.allowActivateForDisabledItems).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the List', () => {
    const disabledIds = [1, 2, 5];

    renderListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
        open: true,
    });

    expect(mockListProps.disabledIds).toBe(disabledIds);
    expect(mockListProps.allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the List', () => {
    renderListOverlay({itemDisabledCondition: 'status == "inactive"', open: true});

    expect(mockListProps.itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass correct flags to the List', () => {
    renderListOverlay({open: true});

    expect(mockListProps.copyable).toEqual(false);
    expect(mockListProps.deletable).toEqual(false);
    expect(mockListProps.movable).toEqual(false);
    expect(mockListProps.orderable).toEqual(false);
    expect(mockListProps.searchable).toEqual(true);
});

test('Should pass confirmLoading and confirmDisabled flag to the Overlay', () => {
    renderListOverlay({open: true, title: 'Test'});

    expect(getConfirmButton()).toBeDisabled();
    expect(getConfirmButton().querySelector('.loader')).not.toBeInTheDocument();
});

test('Should pass confirmLoading and negative confirmDisabled flag to the Overlay', () => {
    renderListOverlay({
        confirmLoading: true,
        open: true,
        preSelectedItems: [{}],
        title: 'Test',
    });

    expect(getConfirmButton()).toBeDisabled();
    expect(getConfirmButton().querySelector('.loader')).toBeInTheDocument();
});

test('Should call onConfirm when the confirm button is clicked', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const {listStore} = renderListOverlay({
        onConfirm: confirmSpy,
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    (listStore: any).selections = [{id: 1}, {id: 2}];

    await user.click(getConfirmButton());

    expect(confirmSpy).toHaveBeenCalledWith();
});

test('Should instantiate the list with the passed adapter', () => {
    const {unmount} = renderListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });

    expect(mockListProps.adapters).toEqual(['table']);

    unmount();

    renderListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });

    expect(mockListProps.adapters).toEqual(['column_list']);
});

test('Should reload on open if reloadOnOpen is set to true', () => {
    const listStore = createListStore();
    const {rerender} = renderListOverlay({
        listStore,
        open: false,
        preSelectedItems: [{id: 1}],
        reloadOnOpen: true,
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.reload).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            reloadOnOpen={true}
            title="test"
        />
    );

    expect(listStore.reset).toHaveBeenCalledWith();
    expect(listStore.reload).toHaveBeenCalledWith();
});

test('Should not reload on open if reloadOnOpen is set to true but listStore is still loading', () => {
    const listStore = createListStore();
    (listStore: any).loading = true;
    const {rerender} = renderListOverlay({
        listStore,
        open: false,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.reload).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.reload).not.toHaveBeenCalled();
});

test('Should not reload on open if reloadOnOpen is not set', () => {
    const listStore = createListStore();
    const {rerender} = renderListOverlay({
        listStore,
        open: false,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.reload).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    expect(listStore.reset).not.toHaveBeenCalled();
    expect(listStore.reload).not.toHaveBeenCalled();
});

test('Should not clear selection on close if clearSelectionOnClose prop is not set', () => {
    const listStore = createListStore();
    const preSelectedItems = [{id: 1}];
    const {rerender} = renderListOverlay({
        clearSelectionOnClose: false,
        listStore,
        open: true,
        preSelectedItems,
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toHaveBeenCalled();
    expect(listStore.select).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={false}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={preSelectedItems}
            title="test"
        />
    );

    expect(listStore.clearSelection).not.toHaveBeenCalled();
});

test('Should clear selection on close if clearSelectionOnClose prop is set', () => {
    const listStore = createListStore();
    const preSelectedItems = [{id: 1}];
    const {rerender} = renderListOverlay({
        clearSelectionOnClose: true,
        listStore,
        open: true,
        preSelectedItems,
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toHaveBeenCalled();
    expect(listStore.select).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={true}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={preSelectedItems}
            title="test"
        />
    );

    expect(listStore.clearSelection).toHaveBeenCalledWith();
});

test('Should update selection if passed preSelectedItems prop changes', () => {
    const listStore = createListStore();
    const preSelectedItems = [{id: 1}];
    const {rerender} = renderListOverlay({
        listStore,
        open: true,
        preSelectedItems,
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={preSelectedItems}
            title="bla"
        />
    );

    expect(listStore.clearSelection).not.toHaveBeenCalled();
    expect(listStore.select).not.toHaveBeenCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 2}]}
            title="bla"
        />
    );

    expect(listStore.clearSelection).toHaveBeenCalledWith();
    expect(listStore.select).toHaveBeenCalledWith({id: 2});
});
