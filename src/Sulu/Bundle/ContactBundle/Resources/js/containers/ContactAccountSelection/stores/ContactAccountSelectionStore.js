// @flow
import {action, computed, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {arrayMove} from 'sulu-admin-bundle/utils';

// TODO extract into separate file?
const CONTACT_PREFIX = 'c';
const ACCOUNT_PREFIX = 'a';

export default class ContactAccountSelectionStore {
    @observable items: Array<Object> = [];
    @observable loading: boolean = false;

    @computed get contactItems(): Array<Object> {
        return this.items
            .filter((item) => item.id.startsWith(CONTACT_PREFIX))
            .map((item) => ({...item, id: parseInt(item.id.substring(CONTACT_PREFIX.length))}));
    }

    @computed get accountItems(): Array<Object> {
        return this.items
            .filter((item) => item.id.startsWith(ACCOUNT_PREFIX))
            .map((item) => ({...item, id: parseInt(item.id.substring(CONTACT_PREFIX.length))}));
    }

    loadItems(itemIds: Array<string>) {
        this.setLoading(true);

        const accountIds = [];
        const contactIds = [];

        itemIds.forEach((id) => {
            if (id.startsWith(CONTACT_PREFIX)) {
                contactIds.push(id.substring(CONTACT_PREFIX.length));
            }

            if (id.startsWith(ACCOUNT_PREFIX)) {
                accountIds.push(id.substring(ACCOUNT_PREFIX.length));
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
                if (id.startsWith(CONTACT_PREFIX)) {
                    const contact = contacts.find((contact) => contact.id == id.substring(CONTACT_PREFIX.length));
                    if (contact) {
                        items.push({...contact, id: CONTACT_PREFIX + contact.id});
                    }
                }

                if (id.startsWith(ACCOUNT_PREFIX)) {
                    const account = accounts.find((acount) => acount.id == id.substring(ACCOUNT_PREFIX.length));
                    if (account) {
                        items.push({...account, id: ACCOUNT_PREFIX + account.id});
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
