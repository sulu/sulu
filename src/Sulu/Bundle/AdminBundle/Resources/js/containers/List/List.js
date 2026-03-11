// @flow
import {observer} from 'mobx-react';
import {action, computed, intercept, observable} from 'mobx';
import React, {Fragment} from 'react';
import equal from 'fast-deep-equal';
import classNames from 'classnames';
import jexl from 'jexl';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import Dialog from '../../components/Dialog';
import Loader from '../../components/Loader';
import PermissionHint from '../../components/PermissionHint';
import userStore from '../../stores/userStore';
import SingleListOverlay from '../SingleListOverlay';
import {translate} from '../../utils';
import DeleteReferencedResourceDialog from '../DeleteReferencedResourceDialog';
import DeleteDependantResourcesDialog from '../DeleteDependantResourcesDialog';
import {ERROR_CODE_DEPENDANT_RESOURCES_FOUND, ERROR_CODE_REFERENCING_RESOURCES_FOUND} from '../../constants';
import ListStore from './stores/ListStore';
import listAdapterRegistry from './registries/listAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import Search from './Search';
import listStyles from './list.scss';
import ColumnOptionsOverlay from './ColumnOptionsOverlay';
import FieldFilter from './FieldFilter';
import type {
    ActionConfig,
    AdapterOptions,
    ItemActionsProvider,
    ResolveCopyArgument,
    ResolveDeleteArgument,
    ResolveMoveArgument,
    ResolveOrderArgument,
    Schema,
    SortOrder,
} from './types';
import type {Node} from 'react';
import type {IValueWillChange} from 'mobx/lib/mobx';
import type {ReferencingResourcesData, DependantResourcesData} from '../../types';

type Props = {|
    actions: Array<ActionConfig>,
    adapterOptions?: {[adapterKey: string]: AdapterOptions},
    adapters: Array<string>,
    allowActivateForDisabledItems: boolean,
    copyable: boolean,
    deletable: boolean,
    disabled: boolean,
    disabledIds: Array<string | number>,
    filterable: boolean,
    header?: Node,
    itemActionsProvider?: ItemActionsProvider,
    itemDisabledCondition?: ?string,
    movable: boolean,
    onCopyFinished?: (response: Object) => void,
    onDeleteError?: (error?: Object) => void,
    onItemAdd?: (id: ?string | number) => void,
    onItemClick?: (itemId: string | number) => void,
    orderable: boolean,
    paginated: boolean,
    searchable: boolean,
    selectable: boolean,
    showColumnOptions: boolean,
    store: ListStore,
    toolbarClassName?: string,
|};

const USER_SETTING_PREFIX = 'sulu_admin.list';
const USER_SETTING_ADAPTER = 'adapter';

@observer
class List extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        allowActivateForDisabledItems: true,
        copyable: true,
        deletable: true,
        disabled: false,
        disabledIds: [],
        filterable: true,
        movable: true,
        orderable: true,
        paginated: true,
        searchable: true,
        selectable: true,
        showColumnOptions: true,
    };

    @observable currentAdapterKey: string;
    @observable showCopyOverlay: boolean = false;
    @observable showDeleteDialog: boolean = false;
    @observable showMoveOverlay: boolean = false;
    @observable showDeleteSelectionDialog: boolean = false;
    @observable allowConflictDeletion: boolean = true;
    @observable showOrderDialog: boolean = false;
    @observable adapterOptionsOpen: boolean = false;
    @observable columnOptionsOpen: boolean = false;
    @observable referencingResourcesData: ?ReferencingResourcesData = undefined;
    @observable dependantResourcesData: ?DependantResourcesData = undefined;
    @observable movingRestrictedTarget: ?Object = undefined;
    resolveCopy: ?(ResolveCopyArgument) => void;
    resolveDelete: ?(ResolveDeleteArgument) => void;
    resolveMove: ?(ResolveMoveArgument) => void;
    resolveOrder: ?(ResolveOrderArgument) => void;
    moveId: ?string | number;
    adapterDisposer: () => void;

    static getAdapterSetting(listKey: string, userSettingsKey: string): string {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_ADAPTER].join('.');

        return userStore.getPersistentSetting(key);
    }

    static setAdapterSetting(listKey: string, userSettingsKey: string, value: *) {
        const key = [USER_SETTING_PREFIX, listKey, userSettingsKey, USER_SETTING_ADAPTER].join('.');

        userStore.setPersistentSetting(key, value);
    }

    @computed get currentAdapter(): typeof AbstractAdapter {
        return listAdapterRegistry.get(this.currentAdapterKey);
    }

    @computed get currentAdapterOptions(): typeof AbstractAdapter {
        return listAdapterRegistry.getOptions(this.currentAdapterKey);
    }

    @computed get disabledIds(): Array<string | number> {
        const {
            disabledIds,
            itemDisabledCondition,
            store,
        } = this.props;

        const disabledItems = itemDisabledCondition
            ? store.visibleItems.filter((item) => jexl.evalSync(itemDisabledCondition, item))
            : [];

        // TODO do not hardcode "id", but use some kind of metadata instead
        return [...disabledIds, ...disabledItems.map((item) => item.id)];
    }

    @computed get showColumnOptions(): boolean {
        return this.currentAdapter.hasColumnOptions && this.props.showColumnOptions;
    }

    constructor(props: Props) {
        super(props);

        this.validateAdapters();

        const {store} = this.props;

        this.adapterDisposer = intercept(this, 'currentAdapterKey', (change: IValueWillChange<*>) => {
            List.setAdapterSetting(store.listKey, store.userSettingsKey, change.newValue);
            return change;
        });
    }

    componentDidUpdate(prevProps: Props) {
        const {adapters, store, paginated} = this.props;
        if (!equal(adapters, prevProps.adapters)) {
            this.validateAdapters();
        }

        if (store !== prevProps.store) {
            store.updateLoadingStrategy(new this.currentAdapter.LoadingStrategy({
                paginated: this.currentAdapter.paginatable && paginated,
            }));
            store.updateStructureStrategy(new this.currentAdapter.StructureStrategy());
        }
    }

    validateAdapters() {
        const {adapters, store} = this.props;

        adapters.forEach((adapterName) => {
            if (!listAdapterRegistry.has(adapterName)) {
                throw new Error(
                    'ListAdapter with the name "' + adapterName + '" does not exist.' +
                    'Did you forget to add it to the "listAdapterRegistry"?'
                );
            }
        });

        if (!this.currentAdapterKey) {
            const adapterKey = List.getAdapterSetting(store.listKey, store.userSettingsKey);
            this.setCurrentAdapterKey(adapterKey || this.props.adapters[0]);
        }
    }

    @action setCurrentAdapterKey = (adapter: string) => {
        this.currentAdapterKey = adapter;

        if (!(this.props.store.loadingStrategy instanceof this.currentAdapter.LoadingStrategy)) {
            this.props.store.updateLoadingStrategy(
                new this.currentAdapter.LoadingStrategy({
                    paginated: this.currentAdapter.paginatable && this.props.paginated,
                })
            );
        }

        if (!(this.props.store.structureStrategy instanceof this.currentAdapter.StructureStrategy)) {
            this.props.store.updateStructureStrategy(new this.currentAdapter.StructureStrategy());
        }
    };

    /** @public */
    @action requestSelectionDelete = (allowConflictDeletion: boolean = true) => {
        this.showDeleteSelectionDialog = true;
        this.allowConflictDeletion = allowConflictDeletion;
    };

    @action handleSelectionDeleteDialogConfirmClick = () => {
        this.props.store.deleteSelection()
            .then(action(() => {
                this.showDeleteSelectionDialog = false;
            }))
            .catch(this.handleDeleteResponseError);
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
                .catch(this.handleDeleteResponseError);

            return response;
        }));

        return deletePromise;
    };

    @action closeAllDialogs = () => {
        this.showDeleteDialog = false;
        this.showDeleteSelectionDialog = false;
        this.referencingResourcesData = undefined;
        this.dependantResourcesData = undefined;
    };

    @action handleDeleteResponseError = (response: Object) => {
        const {onDeleteError} = this.props;

        response.json().then(action((data) => {
            this.closeAllDialogs();

            if (response.status === 409 && data.code === ERROR_CODE_REFERENCING_RESOURCES_FOUND) {
                this.referencingResourcesData = {
                    resource: data.resource,
                    referencingResources: data.referencingResources,
                    referencingResourcesCount: data.referencingResourcesCount,
                };

                const promise: Promise<ResolveDeleteArgument> = new Promise(
                    (resolve) => this.resolveDelete = resolve
                );

                promise.then(action((response) => {
                    if (!response.deleted) {
                        this.closeAllDialogs();

                        return response;
                    }

                    this.props.store.delete(data.resource.id, {force: true})
                        .then(this.closeAllDialogs)
                        .catch(this.handleDeleteResponseError);
                }));

                return;
            }

            if (response.status === 409 && data.code === ERROR_CODE_DEPENDANT_RESOURCES_FOUND) {
                this.dependantResourcesData = {
                    dependantResourceBatches: data.dependantResourceBatches,
                    dependantResourcesCount: data.dependantResourcesCount,
                    detail: data.detail,
                    title: data.title,
                };

                const promise: Promise<ResolveDeleteArgument> = new Promise(
                    (resolve) => this.resolveDelete = resolve
                );

                promise.then(action((response) => {
                    if (!response.deleted) {
                        this.closeAllDialogs();

                        return response;
                    }

                    this.props.store.delete(data.resource.id)
                        .then(this.closeAllDialogs)
                        .catch(this.handleDeleteResponseError);
                }));

                return;
            }

            if (onDeleteError) {
                onDeleteError(data);
            }
        }));
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
        if (!this.moveId) {
            throw new Error('The moveId is not set. This should not happen and is likely a bug.');
        }

        const element = this.props.store.findById(this.moveId);

        if (!element) {
            throw new Error('The moveId does not refer to an element. This should not happen and is likely a bug.');
        }

        if (!element._hasPermissions && !parent._hasPermissions) {
            if (!this.resolveMove) {
                throw new Error('The resolveMove function is not set. This should not happen, and is likely a bug.');
            }

            this.resolveMove({moved: true, parent});
        } else {
            this.movingRestrictedTarget = parent;
        }
    };

    @action handleMoveOverlayClose = () => {
        if (!this.resolveMove) {
            throw new Error('The resolveMove function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveMove({moved: false});
    };

    @action handleMovePermissionWarningConfirm = () => {
        if (!this.resolveMove) {
            throw new Error('The resolveMove function is not set. This should not happen, and is likely a bug.');
        }

        this.resolveMove({moved: true, parent: this.movingRestrictedTarget});
        this.movingRestrictedTarget = undefined;
    };

    @action handleMovePermissionWarningCancel = () => {
        this.movingRestrictedTarget = undefined;
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
            this.props.store.copy(id, response.parent.id, this.props?.onCopyFinished).then(action(() => {
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

    handleFilterChange = (filter: {[string]: mixed}) => {
        this.props.store.filter(filter);
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

        store.visibleItems.forEach((item) => {
            // TODO do not hardcode "id", but use some kind of metadata instead
            if (!this.disabledIds.includes(item.id)) {
                selected ? store.select(item) : store.deselect(item);
            }
        });
    };

    handleAdapterChange = (adapter: string) => {
        this.setCurrentAdapterKey(adapter);
    };

    handleItemActivate = (id: ?string | number) => {
        const {allowActivateForDisabledItems, store} = this.props;

        if (!allowActivateForDisabledItems && this.disabledIds.includes(id)) {
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

    renderDeleteReferencedResourceDialog() {
        if (!this.referencingResourcesData) {
            return null;
        }

        const {store} = this.props;

        return (
            <DeleteReferencedResourceDialog
                allowDeletion={this.allowConflictDeletion}
                confirmLoading={store.deleting}
                onCancel={this.handleDeleteDialogCancelClick}
                onConfirm={this.handleDeleteDialogConfirmClick}
                referencingResourcesData={this.referencingResourcesData}
            />
        );
    }

    @computed get deleteDependantResourcesDialogRequestOptions() {
        const {store} = this.props;

        return store.queryOptions;
    }

    renderDeleteDependantResourcesDialog() {
        if (!this.dependantResourcesData) {
            return null;
        }

        return (
            <DeleteDependantResourcesDialog
                dependantResourcesData={this.dependantResourcesData}
                onCancel={this.handleDeleteDialogCancelClick}
                onFinish={this.handleDeleteDialogConfirmClick}
                requestOptions={this.deleteDependantResourcesDialogRequestOptions}
            />
        );
    }

    render() {
        const {
            actions,
            adapters,
            copyable,
            deletable,
            disabled,
            header,
            itemActionsProvider,
            movable,
            onItemClick,
            onItemAdd,
            paginated,
            orderable,
            adapterOptions,
            selectable,
            store,
            toolbarClassName,
        } = this.props;

        const {
            filterableFields,
            loading,
            schemaLoading,
            userSchema,
        } = store;

        const Adapter = this.currentAdapter;

        const listClass = classNames(
            listStyles.list,
            {
                [listStyles.disabled]: disabled,
            }
        );

        const toolbarClass = classNames(
            listStyles.toolbar,
            toolbarClassName
        );

        const searchable = this.props.searchable && Adapter.searchable;
        const filterable = this.props.filterable && filterableFields && Object.keys(filterableFields).length > 0;

        const hasToolbar = searchable || filterable || actions.length || this.showColumnOptions || adapters.length > 1;

        if (store.forbidden) {
            return <PermissionHint />;
        }

        return (
            <div className={listStyles.listContainer}>
                {header}
                {!schemaLoading && hasToolbar &&
                    <div className={toolbarClass}>
                        <div className={listStyles.toolbarLeft}>
                            {searchable &&
                                <Search onSearch={this.handleSearch} value={store.searchTerm.get()} />
                            }
                            {filterable &&
                                <FieldFilter
                                    fields={filterableFields || {}}
                                    onChange={this.handleFilterChange}
                                    value={store.filterOptions.get()}
                                />
                            }
                        </div>
                        <div className={listStyles.toolbarRight}>
                            {actions.map((action, index) => {
                                const handleClick = action.onClick;

                                return (
                                    <Button
                                        disabled={action.disabled}
                                        icon={action.icon}
                                        key={index}
                                        onClick={handleClick}
                                        skin="icon"
                                    >
                                        {action.label}
                                    </Button>
                                );
                            })}
                            {this.showColumnOptions &&
                                <Fragment>
                                    <ArrowMenu
                                        anchorElement={
                                            <div>
                                                <Button
                                                    icon="su-sort"
                                                    onClick={this.handleAdapterOptionsButtonClick}
                                                    showDropdownIcon={true}
                                                    skin="icon"
                                                />
                                            </div>
                                        }
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
                                        schema={userSchema}
                                    />
                                </Fragment>
                            }
                            <AdapterSwitch
                                adapters={adapters}
                                currentAdapter={this.currentAdapterKey}
                                onAdapterChange={this.handleAdapterChange}
                            />
                        </div>
                    </div>
                }
                <div className={listClass}>
                    {loading && store.pageCount === 0
                        ? <Loader className={listStyles.loader} />
                        : <Adapter
                            active={store.active.get()}
                            activeItems={store.activeItems}
                            adapterOptions={adapterOptions ? adapterOptions[this.currentAdapterKey] : undefined}
                            data={store.data}
                            disabledIds={this.disabledIds}
                            itemActionsProvider={itemActionsProvider}
                            limit={store.limit.get()}
                            loading={loading}
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
                            paginated={paginated}
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
                        {this.renderDeleteReferencedResourceDialog()}
                        {this.renderDeleteDependantResourcesDialog()}
                    </Fragment>
                }
                {movable &&
                    <Fragment>
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
                        <Dialog
                            cancelText={translate('sulu_admin.cancel')}
                            confirmText={translate('sulu_admin.confirm')}
                            onCancel={this.handleMovePermissionWarningCancel}
                            onConfirm={this.handleMovePermissionWarningConfirm}
                            open={!!this.movingRestrictedTarget}
                            title={translate('sulu_security.move_permission_title')}
                        >
                            {translate('sulu_security.move_permission_warning')}
                        </Dialog>
                    </Fragment>
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
