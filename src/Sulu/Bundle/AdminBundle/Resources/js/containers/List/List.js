// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React, {Fragment} from 'react';
import type {Node} from 'react';
import equal from 'fast-deep-equal';
import classNames from 'classnames';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import Dialog from '../../components/Dialog';
import Icon from '../../components/Icon';
import Loader from '../../components/Loader';
import SingleListOverlay from '../SingleListOverlay';
import {translate} from '../../utils/Translator';
import type {
    Action,
    ResolveCopyArgument,
    ResolveDeleteArgument,
    ResolveMoveArgument,
    ResolveOrderArgument,
    Schema,
    SortOrder,
} from './types';
import ListStore from './stores/ListStore';
import listAdapterRegistry from './registries/listAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import Search from './Search';
import listStyles from './list.scss';
import ColumnOptionsOverlay from './ColumnOptionsOverlay';

type Props = {|
    actions?: Array<Action>,
    adapterOptions?: {[adapterKey: string]: {[key: string]: mixed}},
    adapters: Array<string>,
    allowActivateForDisabledItems: boolean,
    copyable: boolean,
    deletable: boolean,
    disabled: boolean,
    disabledIds: Array<string | number>,
    header?: Node,
    movable: boolean,
    onItemAdd?: (id: ?string | number) => void,
    onItemClick?: (itemId: string | number) => void,
    orderable: boolean,
    searchable: boolean,
    selectable: boolean,
    store: ListStore,
|};

@observer
class List extends React.Component<Props> {
    static defaultProps = {
        allowActivateForDisabledItems: true,
        copyable: true,
        deletable: true,
        disabled: false,
        disabledIds: [],
        movable: true,
        orderable: true,
        searchable: true,
        selectable: true,
    };

    @observable currentAdapterKey: string;
    @observable showCopyOverlay: boolean = false;
    @observable showDeleteDialog: boolean = false;
    @observable showDeleteLinkedDialog: boolean = false;
    @observable showMoveOverlay: boolean = false;
    @observable showDeleteSelectionDialog: boolean = false;
    @observable showOrderDialog: boolean = false;
    @observable adapterOptionsOpen: boolean = false;
    @observable columnOptionsOpen: boolean = false;
    @observable referencingItemsForDelete: Array<Object> = [];
    resolveCopy: ?(ResolveCopyArgument) => void;
    resolveDelete: ?(ResolveDeleteArgument) => void;
    resolveMove: ?(ResolveMoveArgument) => void;
    resolveOrder: ?(ResolveOrderArgument) => void;
    moveId: ?string | number;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return listAdapterRegistry.get(this.currentAdapterKey);
    }

    @computed get currentAdapterOptions(): typeof AbstractAdapter {
        return listAdapterRegistry.getOptions(this.currentAdapterKey);
    }

    constructor(props: Props) {
        super(props);

        this.validateAdapters();
    }

    componentDidUpdate(prevProps: Props) {
        const {adapters, store} = this.props;
        if (!equal(adapters, prevProps.adapters)) {
            this.validateAdapters();
        }

        if (store !== prevProps.store) {
            store.updateLoadingStrategy(new this.currentAdapter.LoadingStrategy());
            store.updateStructureStrategy(new this.currentAdapter.StructureStrategy());
        }
    }

    validateAdapters() {
        const {adapters} = this.props;

        adapters.forEach((adapterName) => {
            if (!listAdapterRegistry.has(adapterName)) {
                throw new Error(
                    'ListAdapter with the name "' + adapterName + '" does not exist.' +
                    'Did you forget to add it to the "listAdapterRegistry"?'
                );
            }
        });

        if (!this.currentAdapterKey) {
            this.setCurrentAdapterKey(this.props.adapters[0]);
        }
    }

    @action setCurrentAdapterKey = (adapter: string) => {
        this.currentAdapterKey = adapter;

        if (!(this.props.store.loadingStrategy instanceof this.currentAdapter.LoadingStrategy)) {
            this.props.store.updateLoadingStrategy(new this.currentAdapter.LoadingStrategy());
        }

        if (!(this.props.store.structureStrategy instanceof this.currentAdapter.StructureStrategy)) {
            this.props.store.updateStructureStrategy(new this.currentAdapter.StructureStrategy());
        }
    };

    /** @public */
    @action requestSelectionDelete = () => {
        this.showDeleteSelectionDialog = true;
    };

    @action handleSelectionDeleteDialogConfirmClick = () => {
        this.props.store.deleteSelection().then(action(() => {
            this.showDeleteSelectionDialog = false;
        }));
    };

    @action handleSelectionDeleteDialogCancelClick = () => {
        this.showDeleteSelectionDialog = false;
    };

    @action handleRequestItemDelete = (id: string | number) => {
        this.showDeleteDialog = true;

        const deletePromise: Promise<ResolveDeleteArgument> = new Promise((resolve) => this.resolveDelete = resolve);
        deletePromise.then(action((response) => {
            if (!response.deleted) {
                this.showDeleteDialog = false;
                return response;
            }

            this.props.store.delete(id)
                .then(action(() => {
                    this.showDeleteDialog = false;
                }))
                .catch(action((response) => {
                    if (response.status !== 409) {
                        throw response;
                    }

                    this.showDeleteDialog = false;
                    this.showDeleteLinkedDialog = true;
                    response.json().then(action((data) => {
                        this.referencingItemsForDelete.splice(0, this.referencingItemsForDelete.length);
                        this.referencingItemsForDelete.push(...data.items);

                        const deleteLinkedPromise: Promise<ResolveDeleteArgument> = new Promise(
                            (resolve) => this.resolveDelete = resolve
                        );

                        deleteLinkedPromise.then(action((response) => {
                            if (!response.deleted) {
                                this.showDeleteDialog = this.showDeleteLinkedDialog = false;
                                return response;
                            }

                            this.props.store.delete(id, {force: true})
                                .then(action(() => {
                                    this.showDeleteLinkedDialog = false;
                                }));
                        }));
                    }));
                }));

            return response;
        }));

        return deletePromise;
    };

    @action handleDeleteDialogConfirmClick = () => {
        if (!this.resolveDelete) {
            throw new Error('The resolveDelete function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveDelete({deleted: true});
    };

    @action handleDeleteDialogCancelClick = () => {
        if (!this.resolveDelete) {
            throw new Error('The resolveDelete function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveDelete({deleted: false});
    };

    @action handleRequestItemMove = (id: string | number) => {
        this.moveId = id;
        this.showMoveOverlay = true;

        const movePromise: Promise<ResolveMoveArgument> = new Promise((resolve) => this.resolveMove = resolve);
        movePromise.then(action((response) => {
            if (!response.moved || !response.parent) {
                this.showMoveOverlay = false;
                this.moveId = undefined;
                return response;
            }

            if (!this.moveId) {
                throw new Error('The moveId is not set. This should not happen and is likely a bug.');
            }

            // TODO do not hardcode "id", but use some kind of metadata instead
            this.props.store.move(this.moveId, response.parent.id).then(action(() => {
                this.moveId = undefined;
                this.showMoveOverlay = false;
            }));

            return response;
        }));

        return movePromise;
    };

    @action handleMoveOverlayConfirmClick = (parent: Object) => {
        if (!this.resolveMove) {
            throw new Error('The resolveMove function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveMove({moved: true, parent});
    };

    @action handleMoveOverlayClose = () => {
        if (!this.resolveMove) {
            throw new Error('The resolveMove function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveMove({moved: false});
    };

    @action handleRequestItemCopy = (id: string | number) => {
        this.showCopyOverlay = true;

        const copyPromise: Promise<ResolveCopyArgument> = new Promise((resolve) => this.resolveCopy = resolve);
        copyPromise.then(action((response) => {
            if (!response.copied) {
                this.showCopyOverlay = false;
                return response;
            }

            // TODO do not hardcode "id", but use some kind of metadata instead
            this.props.store.copy(id, response.parent.id).then(action(() => {
                this.showCopyOverlay = false;
            }));

            return response;
        }));

        return copyPromise;
    };

    @action handleCopyOverlayConfirmClick = (parent: Object) => {
        if (!this.resolveCopy) {
            throw new Error('The resolveCopy function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveCopy({copied: true, parent});
    };

    @action handleCopyOverlayClose = () => {
        if (!this.resolveCopy) {
            throw new Error('The resolveCopy function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveCopy({copied: false});
    };

    @action handleRequestItemOrder = (id: string | number, position: number) => {
        this.showOrderDialog = true;

        const orderPromise: Promise<ResolveOrderArgument> = new Promise((resolve) => this.resolveOrder = resolve);
        orderPromise.then(action((response) => {
            if (!response.ordered) {
                this.showOrderDialog = false;
                return response;
            }

            this.props.store.order(id, position).then(action(() => {
                this.showOrderDialog = false;
            }));

            return response;
        }));

        return orderPromise;
    };

    @action handleOrderDialogConfirmClick = () => {
        if (!this.resolveOrder) {
            throw new Error('The resolveOrder function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveOrder({ordered: true});
    };

    @action handleOrderDialogCancelClick = () => {
        if (!this.resolveOrder) {
            throw new Error('The resolveOrder function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveOrder({ordered: false});
    };

    handlePageChange = (page: number) => {
        this.props.store.setPage(page);
    };

    handleLimitChange = (limit: number) => {
        this.props.store.setLimit(limit);
    };

    handleSort = (column: string, order: SortOrder) => {
        this.props.store.sort(column, order);
    };

    handleSearch = (search: ?string) => {
        this.props.store.search(search);
    };

    handleItemSelectionChange = (id: string | number, selected?: boolean) => {
        const {store} = this.props;
        const row = store.findById(id);

        if (!row) {
            return;
        }

        selected ? store.select(row) : store.deselect(row);
    };

    handleAllSelectionChange = (selected?: boolean) => {
        const {store} = this.props;
        selected ? store.selectVisibleItems() : store.deselectVisibleItems();
    };

    handleAdapterChange = (adapter: string) => {
        this.setCurrentAdapterKey(adapter);
    };

    handleItemActivate = (id: string | number) => {
        const {allowActivateForDisabledItems, disabledIds, store} = this.props;

        if (!allowActivateForDisabledItems && disabledIds.includes(id)) {
            return;
        }

        store.activate(id);
    };

    handleItemDeactivate = (id: string | number) => {
        this.props.store.deactivate(id);
    };

    @action handleAdapterOptionsButtonClick = () => {
        this.adapterOptionsOpen = !this.adapterOptionsOpen;
    };

    @action handleAdapterOptionsClose = () => {
        this.adapterOptionsOpen = false;
    };

    @action handleColumnOptionsOpen = () => {
        this.columnOptionsOpen = true;
    };

    @action handleColumnOptionsClose = () => {
        this.columnOptionsOpen = false;
    };

    @action handleColumnOptionsChange = (schema: Schema) => {
        this.columnOptionsOpen = false;
        this.props.store.changeUserSchema(schema);
    };

    renderAdapterOptionsButton() {
        return (
            <div>
                <Button
                    icon="su-sort"
                    onClick={this.handleAdapterOptionsButtonClick}
                    showDropdownIcon={true}
                    skin="icon"
                />
            </div>
        );
    }

    renderAdapterOptions() {
        if (!this.currentAdapter.hasColumnOptions) {
            return null;
        }

        return (
            <Fragment>
                <ArrowMenu
                    anchorElement={this.renderAdapterOptionsButton()}
                    onClose={this.handleAdapterOptionsClose}
                    open={this.adapterOptionsOpen}
                >
                    <ArrowMenu.Section>
                        <ArrowMenu.Action onClick={this.handleColumnOptionsOpen}>
                            {translate('sulu_admin.column_options')}
                        </ArrowMenu.Action>
                    </ArrowMenu.Section>
                </ArrowMenu>
                <ColumnOptionsOverlay
                    onClose={this.handleColumnOptionsClose}
                    onConfirm={this.handleColumnOptionsChange}
                    open={this.columnOptionsOpen}
                    schema={this.props.store.userSchema}
                />
            </Fragment>
        );
    }

    render() {
        const {
            actions,
            adapterOptions,
            adapters,
            copyable,
            deletable,
            disabled,
            disabledIds,
            header,
            movable,
            onItemClick,
            onItemAdd,
            orderable,
            selectable,
            store,
        } = this.props;
        const Adapter = this.currentAdapter;

        const listClass = classNames(
            listStyles.list,
            {
                [listStyles.disabled]: disabled,
            }
        );

        const searchable = this.props.searchable && Adapter.searchable;

        if (store.forbidden) {
            return (
                <div className={listStyles.permissionHint}>
                    <div className={listStyles.permissionIcon}>
                        <Icon name="su-lock" />
                    </div>
                    {translate('sulu_admin.no_permissions')}
                </div>
            );
        }

        return (
            <div className={listStyles.listContainer}>
                {(header || searchable || adapters.length > 1) &&
                    <div className={listStyles.headerContainer}>
                        {header}
                        <div className={listStyles.toolbar}>
                            {searchable &&
                                <Search onSearch={this.handleSearch} value={store.searchTerm.get()} />
                            }
                            {this.renderAdapterOptions()}
                            <AdapterSwitch
                                adapters={adapters}
                                currentAdapter={this.currentAdapterKey}
                                onAdapterChange={this.handleAdapterChange}
                            />
                        </div>
                    </div>
                }
                <div className={listClass}>
                    {store.loading && store.pageCount === 0
                        ? <Loader />
                        : <Adapter
                            actions={actions}
                            active={store.active.get()}
                            activeItems={store.activeItems}
                            adapterOptions={adapterOptions ? adapterOptions[this.currentAdapterKey] : undefined}
                            data={store.data}
                            disabledIds={disabledIds}
                            limit={store.limit.get()}
                            loading={store.loading}
                            onAllSelectionChange={selectable ? this.handleAllSelectionChange : undefined}
                            onItemActivate={this.handleItemActivate}
                            onItemAdd={onItemAdd}
                            onItemClick={onItemClick}
                            onItemDeactivate={this.handleItemDeactivate}
                            onItemSelectionChange={selectable ? this.handleItemSelectionChange : undefined}
                            onLimitChange={this.handleLimitChange}
                            onPageChange={this.handlePageChange}
                            onRequestItemCopy={copyable ? this.handleRequestItemCopy : undefined}
                            onRequestItemDelete={deletable ? this.handleRequestItemDelete : undefined}
                            onRequestItemMove={movable ? this.handleRequestItemMove : undefined}
                            onRequestItemOrder={orderable ? this.handleRequestItemOrder : undefined}
                            onSort={this.handleSort}
                            options={this.currentAdapterOptions}
                            page={store.getPage()}
                            pageCount={store.pageCount}
                            schema={store.userSchema}
                            selections={store.selectionIds}
                            sortColumn={store.sortColumn.get()}
                            sortOrder={store.sortOrder.get()}
                        />
                    }
                </div>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={store.deletingSelection}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleSelectionDeleteDialogCancelClick}
                    onConfirm={this.handleSelectionDeleteDialogConfirmClick}
                    open={this.showDeleteSelectionDialog}
                    title={translate('sulu_admin.delete_warning_title')}
                >
                    {translate('sulu_admin.delete_selection_warning_text', {count: store.selections.length})}
                </Dialog>
                {deletable &&
                    <Fragment>
                        <Dialog
                            cancelText={translate('sulu_admin.cancel')}
                            confirmLoading={store.deleting}
                            confirmText={translate('sulu_admin.ok')}
                            onCancel={this.handleDeleteDialogCancelClick}
                            onConfirm={this.handleDeleteDialogConfirmClick}
                            open={this.showDeleteDialog}
                            title={translate('sulu_admin.delete_warning_title')}
                        >
                            {translate('sulu_admin.delete_warning_text')}
                        </Dialog>
                        <Dialog
                            cancelText={translate('sulu_admin.cancel')}
                            confirmLoading={store.deleting}
                            confirmText={translate('sulu_admin.ok')}
                            onCancel={this.handleDeleteDialogCancelClick}
                            onConfirm={this.handleDeleteDialogConfirmClick}
                            open={this.showDeleteLinkedDialog}
                            title={translate('sulu_admin.delete_linked_warning_title')}
                        >
                            {translate('sulu_admin.delete_linked_warning_text')}
                            <ul>
                                {this.referencingItemsForDelete.map((referencingItem, index) => (
                                    <li key={index}>{referencingItem.name}</li>
                                ))}
                            </ul>
                        </Dialog>
                    </Fragment>
                }
                {movable &&
                    <SingleListOverlay
                        adapter={adapters[0]}
                        allowActivateForDisabledItems={false}
                        clearSelectionOnClose={true}
                        confirmLoading={store.movingSelection || store.moving}
                        disabledIds={this.moveId ? [this.moveId] : []}
                        listKey={store.listKey}
                        locale={store.observableOptions.locale}
                        metadataOptions={store.metadataOptions}
                        onClose={this.handleMoveOverlayClose}
                        onConfirm={this.handleMoveOverlayConfirmClick}
                        open={this.showMoveOverlay}
                        options={store.options}
                        reloadOnOpen={true}
                        resourceKey={store.resourceKey}
                        title={translate('sulu_admin.move_copy_overlay_title')}
                    />
                }
                {copyable &&
                    <SingleListOverlay
                        adapter={adapters[0]}
                        clearSelectionOnClose={true}
                        confirmLoading={store.copying}
                        listKey={store.listKey}
                        locale={store.observableOptions.locale}
                        metadataOptions={store.metadataOptions}
                        onClose={this.handleCopyOverlayClose}
                        onConfirm={this.handleCopyOverlayConfirmClick}
                        open={this.showCopyOverlay}
                        options={store.options}
                        reloadOnOpen={true}
                        resourceKey={store.resourceKey}
                        title={translate('sulu_admin.move_copy_overlay_title')}
                    />
                }
                {orderable &&
                    <Dialog
                        cancelText={translate('sulu_admin.cancel')}
                        confirmLoading={store.ordering}
                        confirmText={translate('sulu_admin.ok')}
                        onCancel={this.handleOrderDialogCancelClick}
                        onConfirm={this.handleOrderDialogConfirmClick}
                        open={this.showOrderDialog}
                        title={translate('sulu_admin.order_warning_title')}
                    >
                        {translate('sulu_admin.order_warning_text')}
                    </Dialog>
                }
            </div>
        );
    }
}

export default List;
