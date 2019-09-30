// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable, toJS} from 'mobx';
import equals from 'fast-deep-equal';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from './stores/ContactAccountSelectionStore';
import contactAccountSelectionStyles from './contactAccountSelection.scss';

type Props = {|
    disabled: boolean,
    onChange: (value: Array<Object>) => void,
    value: Array<Object>,
|};

@observer
class ContactAccountSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        value: [],
    };

    @observable openedOverlayType: ?string = undefined;
    store: ContactAccountSelectionStore;

    constructor(props: Props) {
        super(props);

        const {value} = this.props;

        this.store = new ContactAccountSelectionStore();
        this.store.loadItems(value);
    }

    componentDidUpdate() {
        const {value} = this.props;

        const newIds = toJS(value);
        const loadedIds = this.loadedIds;

        newIds.sort();
        loadedIds.sort();
        if (!equals(newIds, loadedIds) && !this.store.loading) {
            this.store.loadItems(value);
        }
    }

    @computed get loadedIds(): Array<string> {
        return toJS(this.store.items.map((item) => item.id));
    }

    @action handleAddButtonClick = (type: ?string) => {
        this.openedOverlayType = type;
    };

    @action handleOverlayClose = () => {
        this.openedOverlayType = undefined;
    };

    @action handleConfirm(items: Array<Object>, prefix: string) {
        const {onChange, value} = this.props;

        const itemIds = items.map((item) => prefix + item.id);

        onChange([
            ...value.filter((id) => !id.startsWith(prefix) || itemIds.includes(id)),
            ...itemIds.filter((id) => !value.includes(id)),
        ]);

        this.openedOverlayType = undefined;
    }

    @action handleContactConfirm = (contacts: Array<Object>) => {
        this.handleConfirm(contacts, ContactAccountSelectionStore.contactPrefix);
    };

    @action handleAccountConfirm = (accounts: Array<Object>) => {
        this.handleConfirm(accounts, ContactAccountSelectionStore.accountPrefix);
    };

    callChange() {
        const {onChange} = this.props;

        onChange(this.loadedIds);
    }

    handleRemove = (id: string) => {
        this.store.remove(id);
        this.callChange();
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.store.move(oldItemIndex, newItemIndex);
        this.callChange();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={disabled || false}
                    label={translate('sulu_contact.contact_account_selection_label', {count: value ? value.length : 0})}
                    leftButton={{
                        icon: 'su-plus-circle',
                        onClick: this.handleAddButtonClick,
                        options: [
                            {label: translate('sulu_contact.people'), value: 'contacts'},
                            {label: translate('sulu_contact.organizations'), value: 'accounts'},
                        ],
                    }}
                    loading={this.store.loading}
                    onItemsSorted={this.handleSorted}
                >
                    {this.store.items.map((item, index) => (
                        <MultiItemSelection.Item
                            id={item.id}
                            index={index + 1}
                            key={item.id}
                            onRemove={this.handleRemove}
                        >
                            <div className={contactAccountSelectionStyles.item}>
                                {item.fullName || item.name}
                            </div>
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <MultiListOverlay
                    adapter="table"
                    listKey="contacts"
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleContactConfirm}
                    open={this.openedOverlayType === 'contacts'}
                    preloadSelectedItems={false}
                    preSelectedItems={this.store.contactItems}
                    resourceKey="contacts"
                    title={translate('sulu_contact.contact_selection_overlay_title')}
                />
                <MultiListOverlay
                    adapter="table"
                    listKey="accounts"
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleAccountConfirm}
                    open={this.openedOverlayType === 'accounts'}
                    preloadSelectedItems={false}
                    preSelectedItems={this.store.accountItems}
                    resourceKey="accounts"
                    title={translate('sulu_contact.account_selection_overlay_title')}
                />
            </Fragment>
        );
    }
}

export default ContactAccountSelection;
