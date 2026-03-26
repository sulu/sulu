// @flow
import {act, render} from '@testing-library/react';
import React from 'react';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {arrayMove} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from '../stores/ContactAccountSelectionStore';
import ContactAccountSelection from '../ContactAccountSelection';

jest.mock('sulu-admin-bundle/containers', () => ({
    MultiListOverlay: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const MultiItemSelectionMock = jest.fn(() => null);
    (MultiItemSelectionMock: any).Item = jest.fn(() => null);

    return {
        MultiItemSelection: MultiItemSelectionMock,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/ContactAccountSelectionStore', () => jest.fn());

beforeEach(() => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.remove = jest.fn((id) => {
            this.items = this.items.filter((item) => item.id !== id);
        });
        this.move = jest.fn((oldItemIndex, newItemIndex) => {
            this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
        });
        this.items = [];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    ContactAccountSelectionStore.accountPrefix = 'a';
    ContactAccountSelectionStore.contactPrefix = 'c';

    jest.clearAllMocks();
});

function getLatestSelectionProps() {
    const selectionCalls = (MultiItemSelection: any).mock.calls;
    return selectionCalls[selectionCalls.length - 1][0];
}

function getSelectionItems() {
    const selectionProps = getLatestSelectionProps();
    return React.Children.toArray(selectionProps.children);
}

function getLatestSelectionItemProps(itemId: string) {
    const items = getSelectionItems()
        .map((item) => item.props)
        .reverse();

    const itemProps = items.find((props) => props.id === itemId);
    if (!itemProps) {
        throw new Error('Expected MultiItemSelection.Item props for id "' + itemId + '"');
    }

    return itemProps;
}

function getLatestOverlayProps(listKey: 'contacts' | 'accounts') {
    const overlayCalls = (MultiListOverlay: any).mock.calls
        .map(([props]) => props)
        .filter((props) => props.listKey === listKey);

    return overlayCalls[overlayCalls.length - 1];
}

test('Render ContactAccountSelection', () => {
    render(<ContactAccountSelection onChange={jest.fn()} />);

    const selectionProps = getLatestSelectionProps();
    expect(selectionProps.disabled).toEqual(false);
    expect(selectionProps.loading).toEqual(false);
});

test('Render ContactAccountSelection with data', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    render(<ContactAccountSelection onChange={jest.fn()} />);
    expect(getSelectionItems()).toHaveLength(3);
});

test('Render loading ContactAccountSelection', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [];
        this.loading = true;
        this.contactItems = [];
        this.accountItems = [];
    });

    render(<ContactAccountSelection onChange={jest.fn()} />);
    const selectionProps = getLatestSelectionProps();
    expect(selectionProps.loading).toEqual(true);
});

test('Render disabled ContactAccountSelection', () => {
    render(<ContactAccountSelection disabled={true} onChange={jest.fn()} />);
    const selectionProps = getLatestSelectionProps();
    expect(selectionProps.disabled).toEqual(true);
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    render(<ContactAccountSelection onChange={jest.fn()} />);

    const contactOverlayProps = getLatestOverlayProps('contacts');
    const accountOverlayProps = getLatestOverlayProps('accounts');
    expect(contactOverlayProps.preloadSelectedItems).toEqual(false);
    expect(accountOverlayProps.preloadSelectedItems).toEqual(false);
});

test('Load items when being constructed', () => {
    render(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />);
    // $FlowFixMe
    const store = ContactAccountSelectionStore.mock.instances[0];

    expect(store.loadItems).toBeCalledWith(['a1', 'c2']);
});

test('Load items when being updated', () => {
    const {rerender} = render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);
    // $FlowFixMe
    const store = ContactAccountSelectionStore.mock.instances[0];

    rerender(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />);
    expect(store.loadItems).toBeCalledWith(['a1', 'c2']);
});

test('Load items when being updated without infinite loop', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn(() => {
            this.items = [
                {id: 'a1', fullName: 'Acme GmbH'},
                {id: 'c2', fullName: 'Erika Mustermann'},
            ];
        });
        this.items = [];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    const {rerender} = render(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c1', 'c2']} />);
    // $FlowFixMe
    const store = ContactAccountSelectionStore.mock.instances[0];

    expect(store.loadItems).toHaveBeenCalledWith(['a1', 'c1', 'c2']);
    expect(store.loadItems).toHaveBeenCalledTimes(1);

    rerender(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c1', 'c2']} />);
    expect(store.loadItems).toHaveBeenCalledTimes(1);
});

test('Close contact overlay if close callback is fired', () => {
    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(getLatestOverlayProps('contacts').open).toEqual(false);

    act(() => {
        getLatestSelectionProps().leftButton.onClick('contacts');
    });
    expect(getLatestOverlayProps('contacts').open).toEqual(true);

    act(() => {
        getLatestOverlayProps('contacts').onClose();
    });
    expect(getLatestOverlayProps('contacts').open).toEqual(false);
});

test('Confirm contact overlay if confirm callback is fired', () => {
    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'c1', 'c2']} />);

    act(() => {
        getLatestSelectionProps().leftButton.onClick('contacts');
    });
    expect(getLatestOverlayProps('contacts').open).toEqual(true);

    act(() => {
        getLatestOverlayProps('contacts').onConfirm([{id: 1}, {id: 4}]);
    });

    expect(getLatestOverlayProps('contacts').open).toEqual(false);
    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'c4']);
});

test('Remove contact if delete callback is fired', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.remove = jest.fn((id) => {
            this.items = this.items.filter((item) => item.id !== id);
        });
        this.move = jest.fn();
        this.items = [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);
    const firstItemProps = getLatestSelectionItemProps('c2');

    firstItemProps.onRemove('c2');

    // $FlowFixMe
    const store = ContactAccountSelectionStore.mock.instances[0];
    expect(store.remove).toBeCalledWith('c2');
    expect(changeSpy).toBeCalledWith(['a3', 'c3']);
});

test('Confirm account overlay if confirm callback is fired', () => {
    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'a2', 'c1']} />);

    act(() => {
        getLatestSelectionProps().leftButton.onClick('accounts');
    });
    expect(getLatestOverlayProps('accounts').open).toEqual(true);

    act(() => {
        getLatestOverlayProps('accounts').onConfirm([{id: 1}, {id: 4}]);
    });

    expect(getLatestOverlayProps('accounts').open).toEqual(false);
    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'a4']);
});

test('Close account overlay if close callback is fired', () => {
    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    act(() => {
        getLatestSelectionProps().leftButton.onClick('accounts');
    });
    expect(getLatestOverlayProps('accounts').open).toEqual(true);

    act(() => {
        getLatestOverlayProps('accounts').onClose();
    });
    expect(getLatestOverlayProps('accounts').open).toEqual(false);
});

test('Call onItemClick callback when an item is clicked', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    const itemClickSpy = jest.fn();

    render(<ContactAccountSelection onChange={jest.fn()} onItemClick={itemClickSpy} value={['c2', 'a3', 'c3']} />);

    const selectionProps = getLatestSelectionProps();
    selectionProps.onItemClick('c2', {id: 'c2', fullName: 'Max Mustermann'});
    expect(itemClickSpy).toHaveBeenLastCalledWith('c2', {id: 'c2', fullName: 'Max Mustermann'});

    selectionProps.onItemClick('a3', {id: 'a3', name: 'Sulu'});
    expect(itemClickSpy).toHaveBeenLastCalledWith('a3', {id: 'a3', name: 'Sulu'});

    selectionProps.onItemClick('c3', {id: 'c3', fullName: 'Erika Mustermann'});
    expect(itemClickSpy).toHaveBeenLastCalledWith('c3', {id: 'c3', fullName: 'Erika Mustermann'});
});

test('Change order of items', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.move = jest.fn((oldItemIndex, newItemIndex) => {
            this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
        });
        this.items = [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ];
        this.loading = false;
        this.contactItems = [];
        this.accountItems = [];
    });

    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);

    getLatestSelectionProps().onItemsSorted(2, 1);

    expect(changeSpy).toBeCalledWith(['c2', 'c3', 'a3']);
});
