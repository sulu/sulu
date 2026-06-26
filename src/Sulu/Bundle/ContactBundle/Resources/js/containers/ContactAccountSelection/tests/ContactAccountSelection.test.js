// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {arrayMove} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from '../stores/ContactAccountSelectionStore';
import ContactAccountSelection from '../ContactAccountSelection';

let mockMultiListOverlayProps: Object = {};
let mockSortableContainerProps: Object = {};

const mockReact = require('react');

jest.mock('react-sortable-hoc', () => ({
    SortableContainer: (Component) => (props) => {
        mockSortableContainerProps = props;
        return mockReact.createElement(Component, props);
    },
    SortableElement: (Component) => Component,
    SortableHandle: (Component) => Component,
}));

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => jest.fn((props) => {
    mockMultiListOverlayProps[props.listKey] = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': String(props.open),
            'data-testid': props.listKey + '-overlay',
        }
    );
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../stores/ContactAccountSelectionStore', () => jest.fn());

beforeEach(() => {
    mockMultiListOverlayProps = {};
    mockSortableContainerProps = {};

    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [];
        this.loading = false;
    });

    ContactAccountSelectionStore.accountPrefix = 'a';
    ContactAccountSelectionStore.contactPrefix = 'c';
});

function getStore() {
    return (ContactAccountSelectionStore: any).mock.instances[0];
}

async function openOverlay(user, optionName) {
    await user.click(screen.getAllByRole('button')[0]);
    await user.click(screen.getByRole('button', {name: optionName}));
}

function getRemoveButton(itemText) {
    const content = screen.getByText(itemText).closest('.content');
    const button = content && content.parentElement && content.parentElement.querySelector('button');

    if (!button) {
        throw new Error('Expected remove button for item "' + itemText + '"');
    }

    return button;
}

test('Render ContactAccountSelection', () => {
    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
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
    });

    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render loading ContactAccountSelection', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [];
        this.loading = true;
    });

    const {asFragment} = render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render disabled ContactAccountSelection', () => {
    const {asFragment} = render(<ContactAccountSelection disabled={true} onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    render(<ContactAccountSelection onChange={jest.fn()} />);

    expect(mockMultiListOverlayProps.contacts.preloadSelectedItems).toEqual(false);
    expect(mockMultiListOverlayProps.accounts.preloadSelectedItems).toEqual(false);
});

test('Load items when being constructed', () => {
    render(<ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />);

    expect(getStore().loadItems).toHaveBeenCalledWith(['a1', 'c2']);
});

test('Load items when being updated', () => {
    const onChange = jest.fn();
    const {rerender} = render(<ContactAccountSelection onChange={onChange} value={undefined} />);

    rerender(<ContactAccountSelection onChange={onChange} value={['a1', 'c2']} />);

    expect(getStore().loadItems).toHaveBeenCalledWith(['a1', 'c2']);
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
    });

    const onChange = jest.fn();
    const {rerender} = render(<ContactAccountSelection onChange={onChange} value={['a1', 'c1', 'c2']} />);

    expect(getStore().loadItems).toHaveBeenCalledWith(['a1', 'c1', 'c2']);
    expect(getStore().loadItems).toHaveBeenCalledTimes(1);

    rerender(<ContactAccountSelection onChange={onChange} value={['a1', 'c1', 'c2']} />);

    expect(getStore().loadItems).toHaveBeenCalledTimes(1);
});

test('Close contact overlay if overlay close callback is fired', async() => {
    const user = userEvent.setup();
    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');

    await openOverlay(user, 'sulu_contact.people');

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.contacts.onClose();
    });

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');
});

test('Confirm contact overlay if close button is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'c1', 'c2']} />);

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');
    await openOverlay(user, 'sulu_contact.people');
    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.contacts.onConfirm([
            {id: 1},
            {id: 4},
        ]);
    });

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');

    expect(changeSpy).toHaveBeenCalledWith(['a1', 'c1', 'c4']);
});

test('Close contact overlay if close button is clicked', async() => {
    const user = userEvent.setup();
    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');

    await openOverlay(user, 'sulu_contact.people');

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.contacts.onClose();
    });

    expect(screen.getByTestId('contacts-overlay')).toHaveAttribute('data-open', 'false');
});

test('Remove contact if delete button is clicked', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.remove = jest.fn((id) => {
            this.items = this.items.filter((item) => item.id !== id);
        });
        this.items = [
            {id: 'c2', fullName: 'Max Mustermann'},
            {id: 'a3', name: 'Sulu'},
            {id: 'c3', fullName: 'Erika Mustermann'},
        ];
    });

    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);

    await user.click(getRemoveButton('Max Mustermann'));

    expect(getStore().remove).toHaveBeenCalledWith('c2');
    expect(changeSpy).toHaveBeenCalledWith(['a3', 'c3']);
});

test('Confirm account overlay if confirm button is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['a1', 'a2', 'c1']} />);

    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'false');
    await openOverlay(user, 'sulu_contact.organizations');
    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.accounts.onConfirm([
            {id: 1},
            {id: 4},
        ]);
    });

    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'false');

    expect(changeSpy).toHaveBeenCalledWith(['a1', 'c1', 'a4']);
});

test('Close account overlay if close button is clicked', async() => {
    const user = userEvent.setup();
    render(<ContactAccountSelection onChange={jest.fn()} value={undefined} />);

    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'false');

    await openOverlay(user, 'sulu_contact.organizations');

    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.accounts.onClose();
    });

    expect(screen.getByTestId('accounts-overlay')).toHaveAttribute('data-open', 'false');
});

test('Call onItemClick callback when an item is clicked', async() => {
    const user = userEvent.setup();

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
    });

    const itemClickSpy = jest.fn();

    render(<ContactAccountSelection onChange={jest.fn()} onItemClick={itemClickSpy} value={['c2', 'a3', 'c3']} />);

    await user.click(screen.getByText('Max Mustermann'));
    expect(itemClickSpy).toHaveBeenLastCalledWith('c2', {id: 'c2', fullName: 'Max Mustermann'});
    await user.click(screen.getByText('Sulu'));
    expect(itemClickSpy).toHaveBeenLastCalledWith('a3', {id: 'a3', name: 'Sulu'});
    await user.click(screen.getByText('Erika Mustermann'));
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
    });

    const changeSpy = jest.fn();

    render(<ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />);

    act(() => {
        mockSortableContainerProps.onSortEnd({newIndex: 1, oldIndex: 2});
    });

    expect(changeSpy).toHaveBeenCalledWith(['c2', 'c3', 'a3']);
});
