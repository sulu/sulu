// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import {arrayMove} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from '../stores/ContactAccountSelectionStore';
import ContactAccountSelection from '../ContactAccountSelection';

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/ContactAccountSelectionStore', () => jest.fn());

beforeEach(() => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [];
        this.loading = false;
    });

    ContactAccountSelectionStore.accountPrefix = 'a';
    ContactAccountSelectionStore.contactPrefix = 'c';
});

test('Render ContactAccountSelection', () => {
    expect(render(<ContactAccountSelection onChange={jest.fn()} />)).toMatchSnapshot();
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

    expect(render(<ContactAccountSelection onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Render loading ContactAccountSelection', () => {
    // $FlowFixMe
    ContactAccountSelectionStore.mockImplementation(function() {
        this.loadItems = jest.fn();
        this.items = [];
        this.loading = true;
    });

    expect(render(<ContactAccountSelection onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Render disabled ContactAccountSelection', () => {
    expect(render(<ContactAccountSelection disabled={true} onChange={jest.fn()} />)).toMatchSnapshot();
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} />
    );

    expect(contactAccountSelection.find(MultiListOverlay)).toHaveLength(2);
    expect(contactAccountSelection.find(MultiListOverlay).at(0).prop('preloadSelectedItems')).toEqual(false);
    expect(contactAccountSelection.find(MultiListOverlay).at(1).prop('preloadSelectedItems')).toEqual(false);
});

test('Load items when being constructed', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={['a1', 'c2']} />
    );

    expect(contactAccountSelection.instance().store.loadItems).toBeCalledWith(['a1', 'c2']);
});

test('Load items when being updated', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={undefined} />
    );

    contactAccountSelection.setProps({value: ['a1', 'c2']});

    expect(contactAccountSelection.instance().store.loadItems).toBeCalledWith(['a1', 'c2']);
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

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={['a1', 'c1', 'c2']} />
    );

    expect(contactAccountSelection.instance().store.loadItems).toHaveBeenCalledWith(['a1', 'c1', 'c2']);
    expect(contactAccountSelection.instance().store.loadItems).toHaveBeenCalledTimes(1);

    contactAccountSelection.setProps({value: ['a1', 'c1', 'c2']});

    expect(contactAccountSelection.instance().store.loadItems).toHaveBeenCalledTimes(1);
});

test('Close contact overlay if close button is clicked', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={undefined} />
    );

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);
    contactAccountSelection.find('MultiItemSelection').prop('leftButton').onClick('contacts');
    contactAccountSelection.update();

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(true);

    contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('onClose')();
    contactAccountSelection.update();
    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);
});

test('Confirm contact overlay if close button is clicked', () => {
    const changeSpy = jest.fn();

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={changeSpy} value={['a1', 'c1', 'c2']} />
    );

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);
    contactAccountSelection.find('MultiItemSelection').prop('leftButton').onClick('contacts');
    contactAccountSelection.update();

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(true);

    contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('onConfirm')([
        {id: 1},
        {id: 4},
    ]);
    contactAccountSelection.update();
    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);

    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'c4']);
});

test('Close contact overlay if close button is clicked', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={undefined} />
    );

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);
    contactAccountSelection.find('MultiItemSelection').prop('leftButton').onClick('contacts');
    contactAccountSelection.update();

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(true);

    contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('onClose')();
    contactAccountSelection.update();
    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="contacts"]').prop('open')).toEqual(false);
});

test('Remove contact if delete button is clicked', () => {
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

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />
    );

    contactAccountSelection.find('MultiItemSelection Item[index=1]').prop('onRemove')('c2');

    expect(contactAccountSelection.instance().store.remove).toBeCalledWith('c2');
    expect(changeSpy).toBeCalledWith(['a3', 'c3']);
});

test('Confirm account overlay if confirm button is clicked', () => {
    const changeSpy = jest.fn();

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={changeSpy} value={['a1', 'a2', 'c1']} />
    );

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(false);
    contactAccountSelection.find('MultiItemSelection').prop('leftButton').onClick('accounts');
    contactAccountSelection.update();

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(true);

    contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('onConfirm')([
        {id: 1},
        {id: 4},
    ]);
    contactAccountSelection.update();
    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(false);

    expect(changeSpy).toBeCalledWith(['a1', 'c1', 'a4']);
});

test('Close account overlay if close button is clicked', () => {
    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} value={undefined} />
    );

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(false);
    contactAccountSelection.find('MultiItemSelection').prop('leftButton').onClick('accounts');
    contactAccountSelection.update();

    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(true);

    contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('onClose')();
    contactAccountSelection.update();
    expect(contactAccountSelection.find(MultiListOverlay).find('[listKey="accounts"]').prop('open')).toEqual(false);
});

test('Call onItemClick callback when an item is clicked', () => {
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

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={jest.fn()} onItemClick={itemClickSpy} value={['c2', 'a3', 'c3']} />
    );

    contactAccountSelection.find('MultiItemSelection .content').at(0).simulate('click');
    expect(itemClickSpy).toHaveBeenLastCalledWith('c2', {id: 'c2', fullName: 'Max Mustermann'});
    contactAccountSelection.find('MultiItemSelection .content').at(1).simulate('click');
    expect(itemClickSpy).toHaveBeenLastCalledWith('a3', {id: 'a3', name: 'Sulu'});
    contactAccountSelection.find('MultiItemSelection .content').at(2).simulate('click');
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

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={changeSpy} value={['c2', 'a3', 'c3']} />
    );

    contactAccountSelection.find('MultiItemSelection').prop('onItemsSorted')(2, 1);

    expect(changeSpy).toBeCalledWith(['c2', 'c3', 'a3']);
});
