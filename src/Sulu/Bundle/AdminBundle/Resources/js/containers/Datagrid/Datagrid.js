// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React, {Fragment} from 'react';
import type {Node} from 'react';
import equal from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import {translate} from '../../utils/Translator';
import type {SortOrder} from './types';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import MoveOverlay from './MoveOverlay';
import Search from './Search';
import datagridStyles from './datagrid.scss';

type Props = {|
    adapters: Array<string>,
    deletable: boolean,
    disabledIds: Array<string | number>,
    header?: Node,
    movable: boolean,
    onItemClick?: (itemId: string | number) => void,
    onAddClick?: (id: string | number) => void,
    selectable: boolean,
    searchable: boolean,
    store: DatagridStore,
|};

@observer
export default class Datagrid extends React.Component<Props> {
    static defaultProps = {
        deletable: true,
        disabledIds: [],
        movable: true,
        selectable: true,
        searchable: true,
    };

    @observable currentAdapterKey: string;
    @observable deleting: boolean = false;
    @observable moving: boolean = false;
    @observable showDeleteDialog: boolean = false;
    @observable showMoveOverlay: boolean = false;
    deleteId: ?string | number;
    moveId: ?string | number;
    moveOverlayDatagridStore: DatagridStore;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return datagridAdapterRegistry.get(this.currentAdapterKey);
    }

    @computed get currentAdapterOptions(): typeof AbstractAdapter {
        return datagridAdapterRegistry.getOptions(this.currentAdapterKey);
    }

    constructor(props: Props) {
        super(props);

        this.validateAdapters();

        const {movable, store} = this.props;
        if (movable) {
            this.moveOverlayDatagridStore = new DatagridStore(
                store.resourceKey,
                {locale: store.observableOptions.locale, page: observable.box()},
                store.options
            );
        }
    }

    componentDidUpdate(prevProps: Props) {
        if (!equal(this.props.adapters, prevProps.adapters)) {
            this.validateAdapters();
        }

        if (this.props.store !== prevProps.store) {
            this.props.store.updateStrategies(
                new this.currentAdapter.LoadingStrategy(),
                new this.currentAdapter.StructureStrategy()
            );
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
        this.props.store.updateStrategies(
            new this.currentAdapter.LoadingStrategy(),
            new this.currentAdapter.StructureStrategy()
        );
    };

    @action handleDeleteClick = (id: string | number) => {
        this.deleteId = id;
        this.showDeleteDialog = true;
    };

    @action handleDeleteDialogConfirmClick = () => {
        if (!this.deleteId) {
            throw new Error('The id for deletion was not set. This should not happen, and is like caused by a bug.');
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

    @action handleMoveClick = (id: string | number) => {
        this.moveId = id;
        this.showMoveOverlay = true;
    };

    @action handleMoveOverlayConfirmClick = (parentId: string | number) => {
        if (!this.moveId) {
            throw new Error('The id for moving was not set. This should not happen, and is like caused by a bug.');
        }

        this.moving = true;
        this.props.store.move(this.moveId, parentId).then(action(() => {
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
        this.props.store.setActive(undefined); // TODO keep active and expand correctly
        this.setCurrentAdapterKey(adapter);
    };

    handleItemActivation = (id: string | number) => {
        this.props.store.activate(id);
    };

    handleItemDeactivation = (id: string | number) => {
        this.props.store.deactivate(id);
    };

    render() {
        const {
            adapters,
            deletable,
            disabledIds,
            header,
            movable,
            onItemClick,
            onAddClick,
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
                        onAddClick={onAddClick}
                        onAllSelectionChange={selectable ? this.handleAllSelectionChange : undefined}
                        onDeleteClick={deletable ? this.handleDeleteClick : undefined}
                        onItemActivation={this.handleItemActivation}
                        onItemDeactivation={this.handleItemDeactivation}
                        onItemClick={onItemClick}
                        onItemSelectionChange={selectable ? this.handleItemSelectionChange : undefined}
                        onMoveClick={movable ? this.handleMoveClick : undefined}
                        onPageChange={this.handlePageChange}
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
                <MoveOverlay
                    adapters={adapters}
                    disabledId={this.moveId}
                    onConfirm={this.handleMoveOverlayConfirmClick}
                    onClose={this.handleMoveOverlayClose}
                    loading={this.moving}
                    open={this.showMoveOverlay}
                    store={this.moveOverlayDatagridStore}
                />
            </Fragment>
        );
    }
}
