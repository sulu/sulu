// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
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
        <ContactAccountSelection onChange={changeSpy} value={['a1', 'c1']} />
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

test('Confirm account overlay if confirm button is clicked', () => {
    const changeSpy = jest.fn();

    const contactAccountSelection = mount(
        <ContactAccountSelection onChange={changeSpy} value={['a1', 'c1']} />
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
