// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from 'sulu-admin-bundle/services/Router';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactAccountSelectionStore from '../../../ContactAccountSelection/stores/ContactAccountSelectionStore';
import ContactAccountSelection from '../../fields/ContactAccountSelection';

let mockMultiListOverlayProps: Object = {};

const mockReact = require('react');

jest.mock('react-sortable-hoc', () => ({
    SortableContainer: (Component) => Component,
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

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../ContactAccountSelection/stores/ContactAccountSelectionStore', () => jest.fn(function() {
    this.loadItems = jest.fn();

    mockExtendObservable(this, {
        items: [],
    });
}));

beforeEach(() => {
    mockMultiListOverlayProps = {};

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

test('Pass props correctly to ContactAccountSelection component', () => {
    render(<ContactAccountSelection {...fieldTypeDefaultProps} />);

    expect(screen.getByText('sulu_contact.contact_account_selection_label')).toBeInTheDocument();
    expect(screen.getAllByRole('button')[0]).toBeEnabled();
    expect(getStore().loadItems).toHaveBeenCalledWith([]);
});

test('Pass disabled prop to ContactAccountSelection component', () => {
    render(<ContactAccountSelection {...fieldTypeDefaultProps} disabled={true} />);

    expect(screen.getAllByRole('button')[0]).toBeDisabled();
});

test('Pass value prop to ContactAccountSelection component', () => {
    render(<ContactAccountSelection {...fieldTypeDefaultProps} value={['a1', 'c2']} />);

    expect(getStore().loadItems).toHaveBeenCalledWith(['a1', 'c2']);
});

test('Call onChange and onFinish calbacks', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['a1', 'c2']}
        />
    );

    await openOverlay(user, 'sulu_contact.people');

    act(() => {
        mockMultiListOverlayProps.contacts.onConfirm([
            {id: 6},
        ]);
    });

    expect(changeSpy).toHaveBeenCalledWith(['a1', 'c6']);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Call onItemClick callback', async() => {
    const user = userEvent.setup();
    const router = new Router();

    (ContactAccountSelectionStore: any).mockImplementationOnce(function() {
        this.loadItems = jest.fn();

        mockExtendObservable(this, {
            items: [
                {id: 'a1', name: 'Sulu'},
                {id: 'c2', fullName: 'Max Mustermann'},
            ],
        });
    });

    render(
        <ContactAccountSelection
            {...fieldTypeDefaultProps}
            router={router}
            value={['a1', 'c2']}
        />
    );

    await user.click(screen.getByText('Sulu'));
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.account_edit_form', {id: '1'});

    await user.click(screen.getByText('Max Mustermann'));
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.contact_edit_form', {id: '2'});
});
