/* eslint-disable react/jsx-no-bind */
// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import {findMockCallArg} from 'sulu-admin-bundle/utils/TestHelper';
import {arrayMove} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from '../stores/ContactAccountSelectionStore';
import ContactAccountSelection from '../ContactAccountSelection';

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => {
    const React = require('react');

    return jest.fn(function MultiListOverlayMock({listKey, onClose, onConfirm}) {
        function handleConfirm() {
            onConfirm([{id: 1}, {id: 4}]);
        }

        return (
            <div data-testid={'overlay-' + listKey}>
                <button onClick={onClose} type="button">close-{listKey}</button>
                <button onClick={handleConfirm} type="button">confirm-{listKey}</button>
            </div>
        );
    });
});

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const MultiItemSelection: any = jest.fn(function MultiItemSelectionMock({
        children,
        disabled,
        leftButton,
        loading,
        onItemsSorted,
        onItemClick,
    }) {
        const childrenWithOnItemClick = React.Children.map(children, (child) => {
            if (!child) {
                return child;
            }

            return React.cloneElement(child, {onItemClick});
        });

        function handleOpenContacts() {
            leftButton.onClick('contacts');
        }

        function handleOpenAccounts() {
            leftButton.onClick('accounts');
        }

        function handleSortItems() {
            onItemsSorted(2, 1);
        }

        return (
            <div data-testid="multi-item-selection">
                <button disabled={disabled} onClick={handleOpenContacts} type="button">
                    open-contacts
                </button>
                <button disabled={disabled} onClick={handleOpenAccounts} type="button">
                    open-accounts
                </button>
                <button onClick={handleSortItems} type="button">sort-items</button>
                {loading ? <div data-testid="contact-account-selection-loading" /> : null}
                {childrenWithOnItemClick}
            </div>
        );
    });

    MultiItemSelection.Item = jest.fn(function ItemMock({children, id, onItemClick, onRemove, value}) {
        function handleRemove() {
            onRemove(id);
        }

        function handleClick() {
            if (onItemClick) {
                onItemClick(id, value);
            }
        }

        return (
            <div data-testid={'item-' + id}>
                <button onClick={handleRemove} type="button">remove-{id}</button>
                <button onClick={handleClick} type="button">click-{id}</button>
                {children}
            </div>
        );
    });

    return {
        MultiItemSelection,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/ContactAccountSelectionStore', () => jest.fn());

function mockStoreImplementation({items = [], loading = false}: {|items?: Array<Object>, loading?: boolean|} = {}) {
    ContactAccountSelectionStoreMock.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.remove = jest.fn((id) => {
            this.items = this.items.filter((item) => item.id !== id);
        });
        this.move = jest.fn((oldItemIndex, newItemIndex) => {
            this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
        });
        this.items = items;
        this.loading = loading;

        Object.defineProperty(this, 'contactItems', {
            get: () => this.items.filter((item) => item.id.startsWith('c')),
        });
        Object.defineProperty(this, 'accountItems', {
            get: () => this.items.filter((item) => item.id.startsWith('a')),
        });
    });
}

function getLatestOverlayProps(listKey: string) {
    return findMockCallArg(MultiListOverlayMock, ([props]) => props.listKey === listKey);
}

function getStoreInstance() {
    return ContactAccountSelectionStoreMock.mock.instances[ContactAccountSelectionStoreMock.mock.instances.length - 1];
}

const MultiListOverlayMock: any = MultiListOverlay;
const ContactAccountSelectionStoreMock: any = ContactAccountSelectionStore;

beforeEach(() => {
    jest.clearAllMocks();

    mockStoreImplementation();

    ContactAccountSelectionStore.accountPrefix = 'a';
    ContactAccountSelectionStore.contactPrefix = 'c';
});

test('Render ContactAccountSelection', () => {
    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render ContactAccountSelection with data', () => {
    mockStoreImplementation({
        items: [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ],
    });

    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render loading ContactAccountSelection', () => {
    mockStoreImplementation({loading: true});

    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render disabled ContactAccountSelection', () => {
    const {asFragment} = render(<ContactAccountSelection disabled={true} onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(MultiListOverlayMock).toHaveBeenCalledTimes(2);
    expect(getLatestOverlayProps('contacts').preloadSelectedItems).toEqual(false);
    expect(getLatestOverlayProps('accounts').preloadSelectedItems).toEqual(false);
});

test('Load items when being constructed', () => {
    render(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />);

    expect(getStoreInstance().loadItems).toBeCalledWith(['a1', 'c2']);
});

test('Load items when being updated', () => {
    const {rerender} = render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    rerender(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />);

    expect(getStoreInstance().loadItems).toBeCalledWith(['a1', 'c2']);
});

test('Load items when being updated without infinite loop', () => {
    mockStoreImplementation({
        items: [],
    });

    ContactAccountSelectionStoreMock.mockImplementation(function() {
        this.loadItems = jest.fn((value) => {
            this.items = [
                {id: 'a1', fullName: 'Acme GmbH'},
                {id: 'c2', fullName: 'Erika Mustermann'},
            ];

            return value;
        });
        this.remove = jest.fn();
        this.move = jest.fn();
        this.items = [];
        this.loading = false;
        Object.defineProperty(this, 'contactItems', {
            get: () => this.items.filter((item) => item.id.startsWith('c')),
        });
        Object.defineProperty(this, 'accountItems', {
            get: () => this.items.filter((item) => item.id.startsWith('a')),
        });
    });

    const {rerender} = render(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c1', 'c2']} />);

    expect(getStoreInstance().loadItems).toHaveBeenCalledWith(['a1', 'c1', 'c2']);
    expect(getStoreInstance().loadItems).toHaveBeenCalledTimes(1);

    rerender(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c1', 'c2']} />);

    expect(getStoreInstance().loadItems).toHaveBeenCalledTimes(1);
});

test('Close contact overlay if close button is clicked', async() => {
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(getLatestOverlayProps('contacts').open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'open-contacts'}));
    expect(getLatestOverlayProps('contacts').open).toEqual(true);

    await user.click(screen.getByRole('button', {name: 'close-contacts'}));
    expect(getLatestOverlayProps('contacts').open).toEqual(false);
});

test('Confirm contact overlay if close button is clicked', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'c1', 'c2']} />);

    expect(getLatestOverlayProps('contacts').open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'open-contacts'}));
    expect(getLatestOverlayProps('contacts').open).toEqual(true);

    await user.click(screen.getByRole('button', {name: 'confirm-contacts'}));
    expect(getLatestOverlayProps('contacts').open).toEqual(false);

    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'c4']);
});

test('Remove contact if delete button is clicked', async() => {
    mockStoreImplementation({
        items: [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ],
    });

    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);

    await user.click(screen.getByRole('button', {name: 'remove-c2'}));

    expect(getStoreInstance().remove).toBeCalledWith('c2');
    expect(changeSpy).toBeCalledWith(['a3', 'c3']);
});

test('Confirm account overlay if confirm button is clicked', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'a2', 'c1']} />);

    expect(getLatestOverlayProps('accounts').open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'open-accounts'}));
    expect(getLatestOverlayProps('accounts').open).toEqual(true);

    await user.click(screen.getByRole('button', {name: 'confirm-accounts'}));
    expect(getLatestOverlayProps('accounts').open).toEqual(false);

    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'a4']);
});

test('Close account overlay if close button is clicked', async() => {
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(getLatestOverlayProps('accounts').open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'open-accounts'}));
    expect(getLatestOverlayProps('accounts').open).toEqual(true);

    await user.click(screen.getByRole('button', {name: 'close-accounts'}));
    expect(getLatestOverlayProps('accounts').open).toEqual(false);
});

test('Call onItemClick callback when an item is clicked', async() => {
    mockStoreImplementation({
        items: [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ],
    });

    const itemClickSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <ContactAccountSelection onChange={jest.fn()} onItemClick={itemClickSpy} value={['c2', 'a3', 'c3']} />
    );

    await user.click(screen.getByRole('button', {name: 'click-c2'}));
    expect(itemClickSpy).toHaveBeenLastCalledWith('c2', {id: 'c2', fullName: 'Max Mustermann'});

    await user.click(screen.getByRole('button', {name: 'click-a3'}));
    expect(itemClickSpy).toHaveBeenLastCalledWith('a3', {id: 'a3', name: 'Sulu'});

    await user.click(screen.getByRole('button', {name: 'click-c3'}));
    expect(itemClickSpy).toHaveBeenLastCalledWith('c3', {id: 'c3', fullName: 'Erika Mustermann'});
});

test('Change order of items', async() => {
    mockStoreImplementation({
        items: [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ],
    });

    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);

    await user.click(screen.getByRole('button', {name: 'sort-items'}));

    expect(changeSpy).toBeCalledWith(['c2', 'c3', 'a3']);
});
