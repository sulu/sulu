// @flow
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import AddContactToolbarAction from '../../toolbarActions/AddContactToolbarAction';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/SingleAutoComplete', () => jest.fn(function(props) {
    const React = require('react');

    return React.createElement(
        'div',
        {'data-options': JSON.stringify(props.options), 'data-testid': 'contact-select'},
        React.createElement('span', {}, props.selectionStore.item ? props.selectionStore.item.id : 'none'),
        React.createElement(
            'button',
            {onClick: () => props.selectionStore.set({id: 3}), type: 'button'},
            'select contact'
        )
    );
}));

jest.mock('sulu-admin-bundle/containers/ResourceSingleSelect', () => jest.fn(function(props) {
    const React = require('react');

    return React.createElement(
        'div',
        {'data-editable': props.editable, 'data-testid': 'position-select'},
        React.createElement('span', {}, props.value === undefined ? 'none' : props.value),
        React.createElement('button', {onClick: () => props.onChange(5), type: 'button'}, 'select position')
    );
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.options = {};
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    put: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.setMultiple = jest.fn();
}));

function createAddContactToolbarAction() {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new AddContactToolbarAction(listStore, list, router, locales, resourceStore, {});
}

function openOverlay(addContactToolbarAction, rerender) {
    act(() => addContactToolbarAction.getToolbarItemConfig().onClick());
    rerender(addContactToolbarAction.getNode());
}

function finishOverlayCloseTransition() {
    const title = screen.queryByText('sulu_contact.add_contact_to_organization');
    const container = title && title.closest('section')?.parentElement?.parentElement;

    if (container) {
        fireEvent.transitionEnd(container);
    }
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Return config for toolbar item', () => {
    const addContactToolbarAction = createAddContactToolbarAction();

    expect(addContactToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-plus-circle',
        label: 'sulu_admin.add',
        type: 'button',
    }));
});

test('Open dialog if button is clicked', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const {rerender} = render(addContactToolbarAction.getNode());

    expect(screen.queryByText('sulu_contact.add_contact_to_organization')).not.toBeInTheDocument();
    openOverlay(addContactToolbarAction, rerender);
    expect(screen.getByText('sulu_contact.add_contact_to_organization')).toBeInTheDocument();
});

test('Pass correct options to components', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;
    const {rerender} = render(addContactToolbarAction.getNode());

    openOverlay(addContactToolbarAction, rerender);

    expect(screen.getByTestId('position-select')).toHaveAttribute('data-editable', 'true');
    expect(screen.getByTestId('contact-select'))
        .toHaveAttribute('data-options', JSON.stringify({excludedAccountId: 4, flat: false}));
});

test('Reset fields if overlay is just closed', async() => {
    const user = userEvent.setup();
    const addContactToolbarAction = createAddContactToolbarAction();
    const {rerender} = render(addContactToolbarAction.getNode());

    openOverlay(addContactToolbarAction, rerender);
    await user.click(screen.getByRole('button', {name: 'select contact'}));
    await user.click(screen.getByRole('button', {name: 'select position'}));
    rerender(addContactToolbarAction.getNode());

    expect(screen.getByTestId('contact-select')).toHaveTextContent('3');
    expect(screen.getByTestId('position-select')).toHaveTextContent('5');
    await user.click(screen.getByRole('button', {name: 'su-times'}));
    rerender(addContactToolbarAction.getNode());
    finishOverlayCloseTransition();

    expect(screen.queryByText('sulu_contact.add_contact_to_organization')).not.toBeInTheDocument();

    openOverlay(addContactToolbarAction, rerender);
    expect(screen.getByTestId('contact-select')).toHaveTextContent('none');
    expect(screen.getByTestId('position-select')).toHaveTextContent('none');

    expect(ResourceRequester.put).not.toHaveBeenCalled();
});

test('Add selected contact to current account', async() => {
    const user = userEvent.setup();
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;
    let resolvePut: () => void = () => {};
    const putPromise = new Promise((resolve) => {
        resolvePut = resolve;
    });
    ResourceRequester.put.mockReturnValue(putPromise);
    const {rerender} = render(addContactToolbarAction.getNode());

    openOverlay(addContactToolbarAction, rerender);
    const confirmButton = screen.getByRole('button', {name: 'sulu_admin.add'});
    expect(confirmButton).toBeDisabled();
    await user.click(screen.getByRole('button', {name: 'select contact'}));
    rerender(addContactToolbarAction.getNode());

    expect(confirmButton).toBeEnabled();

    await user.click(confirmButton);
    rerender(addContactToolbarAction.getNode());
    expect(confirmButton).toBeDisabled();

    expect(ResourceRequester.put).toHaveBeenCalledWith('account_contacts', {position: undefined}, {
        accountId: 4,
        id: 3,
    });

    await act(async() => {
        resolvePut();
        await putPromise;
    });
    rerender(addContactToolbarAction.getNode());
    finishOverlayCloseTransition();

    expect(screen.queryByText('sulu_contact.add_contact_to_organization')).not.toBeInTheDocument();
    expect(addContactToolbarAction.listStore.reload).toHaveBeenCalledWith();
});

test('Add selected contact to current account with position', async() => {
    const user = userEvent.setup();
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;
    let resolvePut: () => void = () => {};
    const putPromise = new Promise((resolve) => {
        resolvePut = resolve;
    });
    ResourceRequester.put.mockReturnValue(putPromise);
    const {rerender} = render(addContactToolbarAction.getNode());

    openOverlay(addContactToolbarAction, rerender);
    await user.click(screen.getByRole('button', {name: 'select contact'}));
    await user.click(screen.getByRole('button', {name: 'select position'}));
    rerender(addContactToolbarAction.getNode());
    await user.click(screen.getByRole('button', {name: 'sulu_admin.add'}));
    rerender(addContactToolbarAction.getNode());

    expect(ResourceRequester.put).toHaveBeenCalledWith('account_contacts', {position: 5}, {accountId: 4, id: 3});

    await act(async() => {
        resolvePut();
        await putPromise;
    });
    rerender(addContactToolbarAction.getNode());
    finishOverlayCloseTransition();

    expect(screen.queryByText('sulu_contact.add_contact_to_organization')).not.toBeInTheDocument();
    expect(addContactToolbarAction.listStore.reload).toHaveBeenCalledWith();
});
