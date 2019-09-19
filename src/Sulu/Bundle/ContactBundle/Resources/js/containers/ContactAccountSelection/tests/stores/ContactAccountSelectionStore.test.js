// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import ContactAccountSelectionStore from '../../stores/ContactAccountSelectionStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Load accounts and contacts', () => {
    const contactAccountSelectionStore = new ContactAccountSelectionStore();

    const contact1 = {id: 1, fullName: 'Max Mustermann'};
    const contact3 = {id: 3, fullName: 'John Doe'};
    const account1 = {id: 1, name: 'Sulu'};
    const account5 = {id: 5, name: 'Example company'};

    const contactsPromise = Promise.resolve({_embedded: {contacts: [contact1, contact3]}});
    const accountsPromise = Promise.resolve({_embedded: {accounts: [account1, account5]}});

    ResourceRequester.getList.mockImplementation((resourceKey) => {
        if (resourceKey === 'contacts') {
            return contactsPromise;
        }

        if (resourceKey === 'accounts') {
            return accountsPromise;
        }
    });

    expect(contactAccountSelectionStore.loading).toEqual(false);
    contactAccountSelectionStore.loadItems(['c1', 'a1', 'c3', 'a5']);
    expect(contactAccountSelectionStore.loading).toEqual(true);

    expect(ResourceRequester.getList).toBeCalledWith('contacts', {ids: '1,3', limit: undefined, page: 1});
    expect(ResourceRequester.getList).toBeCalledWith('accounts', {ids: '1,5', limit: undefined, page: 1});

    return Promise.all([contactsPromise, accountsPromise]).then(() => {
        expect(contactAccountSelectionStore.loading).toEqual(false);

        expect(contactAccountSelectionStore.items).toHaveLength(4);
        expect(contactAccountSelectionStore.items[0]).toEqual({...contact1, id: 'c1'});
        expect(contactAccountSelectionStore.items[1]).toEqual({...account1, id: 'a1'});
        expect(contactAccountSelectionStore.items[2]).toEqual({...contact3, id: 'c3'});
        expect(contactAccountSelectionStore.items[3]).toEqual({...account5, id: 'a5'});

        expect(contactAccountSelectionStore.contactItems[0]).toEqual(contact1);
        expect(contactAccountSelectionStore.contactItems[1]).toEqual(contact3);
        expect(contactAccountSelectionStore.accountItems[0]).toEqual(account1);
        expect(contactAccountSelectionStore.accountItems[1]).toEqual(account5);
    });
});

test('Do not send any requests if nothing has to be loaded', (done) => {
    const contactAccountSelectionStore = new ContactAccountSelectionStore();

    expect(contactAccountSelectionStore.loading).toEqual(false);
    contactAccountSelectionStore.loadItems([]);
    expect(contactAccountSelectionStore.loading).toEqual(true);

    expect(ResourceRequester.getList).not.toBeCalled();

    setTimeout(() => {
        expect(contactAccountSelectionStore.loading).toEqual(false);
        expect(contactAccountSelectionStore.items).toHaveLength(0);
        expect(contactAccountSelectionStore.contactItems).toHaveLength(0);
        expect(contactAccountSelectionStore.accountItems).toHaveLength(0);

        done();
    });
});

test('Send only contact request if nothing has to be loaded', (done) => {
    const contactAccountSelectionStore = new ContactAccountSelectionStore();

    const contact1 = {id: 1, fullName: 'Max Mustermann'};
    const contact3 = {id: 3, fullName: 'John Doe'};

    const contactsPromise = Promise.resolve({_embedded: {contacts: [contact1, contact3]}});

    ResourceRequester.getList.mockReturnValue(contactsPromise);

    expect(contactAccountSelectionStore.loading).toEqual(false);
    contactAccountSelectionStore.loadItems(['c1', 'c3']);
    expect(contactAccountSelectionStore.loading).toEqual(true);

    expect(ResourceRequester.getList).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.getList).toBeCalledWith('contacts', {ids: '1,3', limit: undefined, page: 1});

    setTimeout(() => {
        expect(contactAccountSelectionStore.loading).toEqual(false);
        expect(contactAccountSelectionStore.items).toHaveLength(2);
        expect(contactAccountSelectionStore.contactItems).toHaveLength(2);
        expect(contactAccountSelectionStore.accountItems).toHaveLength(0);

        done();
    });
});

test('Send only accounts request if nothing has to be loaded', (done) => {
    const contactAccountSelectionStore = new ContactAccountSelectionStore();

    const account1 = {id: 1, name: 'Sulu'};
    const account5 = {id: 5, name: 'Example company'};

    const accountsPromise = Promise.resolve({_embedded: {accounts: [account1, account5]}});

    ResourceRequester.getList.mockReturnValue(accountsPromise);

    expect(contactAccountSelectionStore.loading).toEqual(false);
    contactAccountSelectionStore.loadItems(['a1', 'a5']);
    expect(contactAccountSelectionStore.loading).toEqual(true);

    expect(ResourceRequester.getList).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.getList).toBeCalledWith('accounts', {ids: '1,5', limit: undefined, page: 1});

    setTimeout(() => {
        expect(contactAccountSelectionStore.loading).toEqual(false);
        expect(contactAccountSelectionStore.items).toHaveLength(2);
        expect(contactAccountSelectionStore.contactItems).toHaveLength(0);
        expect(contactAccountSelectionStore.accountItems).toHaveLength(2);

        done();
    });
});
