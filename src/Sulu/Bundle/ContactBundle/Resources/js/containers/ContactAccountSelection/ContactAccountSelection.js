// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import equals from 'fast-deep-equal';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import ContactAccountSelectionStore from './stores/ContactAccountSelectionStore';
import contactAccountSelectionStyles from './contactAccountSelection.scss';

// TODO extract into separate file?
const CONTACT_PREFIX = 'c';
const ACCOUNT_PREFIX = 'a';

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

    @observable openedOverlay: ?string = undefined;
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
        const loadedIds = toJS(this.store.items.map((item) => item.id));

        newIds.sort();
        loadedIds.sort();
        if (!equals(newIds, loadedIds) && !this.store.loading) {
            this.store.loadItems(value);
        }
    }

    @action handleAddButtonClick = (type: ?string) => {
        this.openedOverlay = type;
    };

    @action handleOverlayClose = () => {
        this.openedOverlay = undefined;
    };

    @action handleConfirm(items: Array<Object>, prefix: string) {
        const {onChange, value} = this.props;

        const itemIds = items.map((contact) => prefix + contact.id);

        onChange([
            ...value,
            ...itemIds,
        ].filter((item, index, items) => index == items.indexOf(item)));

        this.openedOverlay = undefined;
    }

    @action handleContactConfirm = (contacts: Array<Object>) => {
        this.handleConfirm(contacts, CONTACT_PREFIX);
    };

    @action handleAccountConfirm = (accounts: Array<Object>) => {
        this.handleConfirm(accounts, ACCOUNT_PREFIX);
    };

    handleRemove = (id: string) => {
        const {onChange, value} = this.props;

        this.store.remove(id);
        onChange([...value.filter((itemId) => itemId !== id)]);
    };

    render() {
        const {disabled} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={disabled || false}
                    leftButton={{
                        icon: 'su-plus-circle',
                        onClick: this.handleAddButtonClick,
                        options: [
                            {label: translate('sulu_contact.people'), value: 'contacts'},
                            {label: translate('sulu_contact.organizations'), value: 'accounts'},
                        ],
                    }}
                    loading={this.store.loading}
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
                    open={this.openedOverlay === 'contacts'}
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
                    open={this.openedOverlay === 'accounts'}
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
