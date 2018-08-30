// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React, {Fragment} from 'react';
import type {Node} from 'react';
import equal from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import SingleDatagridOverlay from '../SingleDatagridOverlay';
import {translate} from '../../utils/Translator';
import type {SortOrder} from './types';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import Search from './Search';
import datagridStyles from './datagrid.scss';

type Props = {|
    adapters: Array<string>,
    allowActivateForDisabledItems: boolean,
    copyable: boolean,
    deletable: boolean,
    disabledIds: Array<string | number>,
    header?: Node,
    movable: boolean,
    onItemClick?: (itemId: string | number) => void,
    onItemAdd?: (id: string | number) => void,
    orderable: boolean,
    selectable: boolean,
    searchable: boolean,
    store: DatagridStore,
|};

@observer
export default class Datagrid extends React.Component<Props> {
    static defaultProps = {
        allowActivateForDisabledItems: true,
        copyable: true,
        deletable: true,
        disabledIds: [],
        movable: true,
        orderable: true,
        selectable: true,
        searchable: true,
    };

    @observable currentAdapterKey: string;
    @observable copying: boolean = false;
    @observable deleting: boolean = false;
    @observable moving: boolean = false;
    @observable showCopyOverlay: boolean = false;
    @observable showDeleteDialog: boolean = false;
    @observable showMoveOverlay: boolean = false;
    @observable showOrderDialog: boolean = false;
    @observable ordering: boolean = false;
    copyId: ?string | number;
    deleteId: ?string | number;
    moveId: ?string | number;
    deleteId: ?string | number;
    orderId: ?string | number;
    orderPosition: ?number;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return datagridAdapterRegistry.get(this.currentAdapterKey);
    }

    @computed get currentAdapterOptions(): typeof AbstractAdapter {
        return datagridAdapterRegistry.getOptions(this.currentAdapterKey);
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
            if (!datagridAdapterRegistry.has(adapterName)) {
                throw new Error(
                    'DatagridAdapter with the name "' + adapterName + '" does not exist.' +
                    'Did you forget to add it to the "datagridAdapterRegistry"?'
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

    @action handleRequestItemDelete = (id: string | number) => {
        this.deleteId = id;
        this.showDeleteDialog = true;
    };

    @action handleDeleteDialogConfirmClick = () => {
        if (!this.deleteId) {
            throw new Error('The id for deletion was not set. This should not happen, and is likely a bug.');
        }

        this.deleting = true;
        this.props.store.delete(this.deleteId).then(action(() => {
            this.hideDeleteDialog();
            this.deleting = false;
        }));
    };

    @action handleDeleteDialogCancelClick = () => {
        this.hideDeleteDialog();
    };

    @action hideDeleteDialog() {
        this.deleteId = undefined;
        this.showDeleteDialog = false;
    }

    @action handleRequestItemMove = (id: string | number) => {
        this.moveId = id;
        this.showMoveOverlay = true;
    };

    @action handleMoveOverlayConfirmClick = (parent: Object) => {
        if (!this.moveId) {
            throw new Error('The id for moving was not set. This should not happen and is likely a bug.');
        }

        this.moving = true;
        // TODO do not hardcode "id", but use some kind of metadata instead
        this.props.store.move(this.moveId, parent.id).then(action(() => {
            this.moving = false;
            this.hideMoveOverlay();
        }));
    };

    @action handleMoveOverlayClose = () => {
        this.hideMoveOverlay();
    };

    @action hideMoveOverlay() {
        this.showMoveOverlay = false;
        this.moveId = undefined;
    }

    @action handleRequestItemCopy = (id: string | number) => {
        this.copyId = id;
        this.showCopyOverlay = true;
    };

    @action handleCopyOverlayConfirmClick = (parent: Object) => {
        if (!this.copyId) {
            throw new Error('The id for moving was not set. This should not happen and is likely a bug.');
        }

        this.copying = true;
        // TODO do not hardcode "id", but use some kind of metadata instead
        this.props.store.copy(this.copyId, parent.id).then(action(() => {
            this.copying = false;
            this.hideCopyOverlay();
        }));
    };

    @action handleCopyOverlayClose = () => {
        this.hideCopyOverlay();
    };

    @action hideCopyOverlay() {
        this.showCopyOverlay = false;
        this.copyId = undefined;
    }

    @action handleRequestItemOrder = (id: string | number, position: number) => {
        this.orderId = id;
        this.orderPosition = position;
        this.showOrderDialog = true;
    };

    @action handleOrderDialogConfirmClick = () => {
        if (!this.orderId) {
            throw new Error('The id for ordering was not set. This should not happen, and is likely a bug.');
        }

        if (!this.orderPosition) {
            throw new Error('The position for ordering was not set. This should not happen, and is likely a bug.');
        }

        this.ordering = true;
        this.props.store.order(this.orderId, this.orderPosition).then(action(() => {
            this.hideOrderDialog();
            this.ordering = false;
        }));
    };

    @action handleOrderDialogCancelClick = () => {
        this.hideOrderDialog();
    };

    @action hideOrderDialog() {
        this.orderId = undefined;
        this.orderPosition = undefined;
        this.showOrderDialog = false;
    }

    handlePageChange = (page: number) => {
        this.props.store.setPage(page);
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

    render() {
        const {
            adapters,
            copyable,
            deletable,
            disabledIds,
            header,
            movable,
            onItemClick,
            onItemAdd,
            orderable,
            searchable,
            selectable,
            store,
        } = this.props;
        const Adapter = this.currentAdapter;

        return (
            <Fragment>
                {(header || searchable || adapters.length > 1) &&
                    <div className={datagridStyles.headerContainer}>
                        {header}
                        <div className={datagridStyles.toolbar}>
                            {searchable &&
                                <Search onSearch={this.handleSearch} value={store.searchTerm.get()} />
                            }
                            <AdapterSwitch
                                adapters={adapters}
                                currentAdapter={this.currentAdapterKey}
                                onAdapterChange={this.handleAdapterChange}
                            />
                        </div>
                    </div>
                }
                <div className={datagridStyles.datagrid}>
                    <Adapter
                        active={store.active.get()}
                        activeItems={store.activeItems}
                        data={store.data}
                        disabledIds={disabledIds}
                        loading={store.loading}
                        onAllSelectionChange={selectable ? this.handleAllSelectionChange : undefined}
                        onItemActivate={this.handleItemActivate}
                        onItemAdd={onItemAdd}
                        onItemDeactivate={this.handleItemDeactivate}
                        onItemClick={onItemClick}
                        onItemSelectionChange={selectable ? this.handleItemSelectionChange : undefined}
                        onPageChange={this.handlePageChange}
                        onRequestItemCopy={copyable ? this.handleRequestItemCopy : undefined}
                        onRequestItemDelete={deletable ? this.handleRequestItemDelete : undefined}
                        onRequestItemMove={movable ? this.handleRequestItemMove : undefined}
                        onRequestItemOrder={orderable ? this.handleRequestItemOrder : undefined}
                        onSort={this.handleSort}
                        options={this.currentAdapterOptions}
                        page={store.getPage()}
                        pageCount={store.pageCount}
                        schema={store.schema}
                        sortColumn={store.sortColumn.get()}
                        sortOrder={store.sortOrder.get()}
                        selections={store.selectionIds}
                    />
                </div>
                {deletable &&
                    <Dialog
                        confirmLoading={this.deleting}
                        cancelText={translate('sulu_admin.cancel')}
                        confirmText={translate('sulu_admin.ok')}
                        onCancel={this.handleDeleteDialogCancelClick}
                        onConfirm={this.handleDeleteDialogConfirmClick}
                        open={this.showDeleteDialog}
                        title={translate('sulu_admin.delete_warning_title')}
                    >
                        {translate('sulu_admin.delete_warning_text')}
                    </Dialog>
                }
                {movable &&
                    <SingleDatagridOverlay
                        adapter={adapters[0]}
                        allowActivateForDisabledItems={false}
                        clearSelectionOnClose={true}
                        confirmLoading={this.moving}
                        disabledIds={this.moveId ? [this.moveId] : []}
                        locale={store.observableOptions.locale}
                        onClose={this.handleMoveOverlayClose}
                        onConfirm={this.handleMoveOverlayConfirmClick}
                        open={this.showMoveOverlay}
                        options={store.options}
                        resourceKey={store.resourceKey}
                        title={translate('sulu_admin.move_copy_overlay_title')}
                    />
                }
                {copyable &&
                    <SingleDatagridOverlay
                        adapter={adapters[0]}
                        clearSelectionOnClose={true}
                        confirmLoading={this.copying}
                        locale={store.observableOptions.locale}
                        onClose={this.handleCopyOverlayClose}
                        onConfirm={this.handleCopyOverlayConfirmClick}
                        open={this.showCopyOverlay}
                        options={store.options}
                        resourceKey={store.resourceKey}
                        title={translate('sulu_admin.move_copy_overlay_title')}
                    />
                }
                {orderable &&
                    <Dialog
                        confirmLoading={this.ordering}
                        cancelText={translate('sulu_admin.cancel')}
                        confirmText={translate('sulu_admin.ok')}
                        onCancel={this.handleOrderDialogCancelClick}
                        onConfirm={this.handleOrderDialogConfirmClick}
                        open={this.showOrderDialog}
                        title={translate('sulu_admin.order_warning_title')}
                    >
                        {translate('sulu_admin.order_warning_text')}
                    </Dialog>
                }
            </Fragment>
        );
    }
}
