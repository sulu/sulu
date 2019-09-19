// @flow
import {action, computed, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {arrayMove} from 'sulu-admin-bundle/utils';

export default class ContactAccountSelectionStore {
    static contactPrefix = 'c';
    static accountPrefix = 'a';

    @observable items: Array<Object> = [];
    @observable loading: boolean = false;

    @computed get contactItems(): Array<Object> {
        return this.items
            .filter((item) => item.id.startsWith(ContactAccountSelectionStore.contactPrefix))
            .map((item) => ({...item, id: parseInt(item.id.substring(ContactAccountSelectionStore.contactPrefix.length))}));
    }

    @computed get accountItems(): Array<Object> {
        return this.items
            .filter((item) => item.id.startsWith(ContactAccountSelectionStore.accountPrefix))
            .map((item) => ({...item, id: parseInt(item.id.substring(ContactAccountSelectionStore.contactPrefix.length))}));
    }

    loadItems(itemIds: Array<string>) {
        this.setLoading(true);

        const accountIds = [];
        const contactIds = [];

        itemIds.forEach((id) => {
            if (id.startsWith(ContactAccountSelectionStore.contactPrefix)) {
                contactIds.push(id.substring(ContactAccountSelectionStore.contactPrefix.length));
            }

            if (id.startsWith(ContactAccountSelectionStore.accountPrefix)) {
                accountIds.push(id.substring(ContactAccountSelectionStore.accountPrefix.length));
            }
        });

        const contactsPromise = contactIds.length > 0
            ? ResourceRequester.getList('contacts', {
                ids: contactIds.join(','),
                limit: undefined,
                page: 1,
            })
            : Promise.resolve({_embedded: {contacts: []}});

        const accountsPromise = accountIds.length > 0
            ? ResourceRequester.getList('accounts', {
                ids: accountIds.join(','),
                limit: undefined,
                page: 1,
            })
            : Promise.resolve({_embedded: {accounts: []}});

        Promise.all([contactsPromise, accountsPromise]).then(action(([contactsResponse, accountsResponse]) => {
            const contacts = contactsResponse._embedded.contacts;
            const accounts = accountsResponse._embedded.accounts;

            this.items = itemIds.reduce((items, id) => {
                if (id.startsWith(ContactAccountSelectionStore.contactPrefix)) {
                    const contact = contacts.find((contact) => contact.id == id.substring(ContactAccountSelectionStore.contactPrefix.length));
                    if (contact) {
                        items.push({...contact, id: ContactAccountSelectionStore.contactPrefix + contact.id});
                    }
                }

                if (id.startsWith(ContactAccountSelectionStore.accountPrefix)) {
                    const account = accounts.find((acount) => acount.id == id.substring(ContactAccountSelectionStore.accountPrefix.length));
                    if (account) {
                        items.push({...account, id: ContactAccountSelectionStore.accountPrefix + account.id});
                    }
                }

                return items;
            }, []);
            this.setLoading(false);
        }));
    }

    @action remove(id: string) {
        this.items = this.items.filter((item) => item.id !== id);
    }

    @action move(oldItemIndex: number, newItemIndex: number) {
        this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }
}
