// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React, {Fragment} from 'react';
import equal from 'fast-deep-equal';
import type {SortOrder} from './types';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import Search from './Search';
import datagridStyles from './datagrid.scss';

type Props = {|
    adapters: Array<string>,
    disabledIds: Array<string | number>,
    onItemClick?: (itemId: string | number) => void,
    onAddClick?: (id: string | number) => void,
    selectable: boolean,
    searchable: boolean,
    store: DatagridStore,
|};

@observer
export default class Datagrid extends React.Component<Props> {
    static defaultProps = {
        disabledIds: [],
        selectable: true,
        searchable: true,
    };

    @observable currentAdapterKey: string;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return datagridAdapterRegistry.get(this.currentAdapterKey);
    }

    constructor(props: Props) {
        super(props);

        this.validateAdapters();
    }

    componentWillReceiveProps(nextProps: Props) {
        if (!equal(this.props.adapters, nextProps.adapters)) {
            this.validateAdapters();
        }

        if (this.props.store !== nextProps.store) {
            nextProps.store.updateStrategies(
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
        selected ? store.selectEntirePage() : store.deselectEntirePage();
    };

    handleAdapterChange = (adapter: string) => {
        this.props.store.setActive(undefined); // TODO keep active and expand correctly
        this.setCurrentAdapterKey(adapter);
    };

    handleItemActivation = (id: string | number) => {
        this.props.store.setActive(id);
    };

    render() {
        const {
            adapters,
            disabledIds,
            onItemClick,
            onAddClick,
            searchable,
            selectable,
            store,
        } = this.props;
        const Adapter = this.currentAdapter;

        return (
            <Fragment>
                <div className={datagridStyles.toolbar}>
                    {searchable &&
                        <Search onSearch={this.handleSearch} value={store.searchTerm ? store.searchTerm.get() : null} />
                    }
                    <AdapterSwitch
                        adapters={adapters}
                        currentAdapter={this.currentAdapterKey}
                        onAdapterChange={this.handleAdapterChange}
                    />
                </div>
                <div className={datagridStyles.datagrid}>
                    <Adapter
                        active={store.active}
                        data={store.data}
                        disabledIds={disabledIds}
                        loading={store.loading}
                        onAddClick={onAddClick}
                        onAllSelectionChange={selectable ? this.handleAllSelectionChange : undefined}
                        onItemActivation={this.handleItemActivation}
                        onItemClick={onItemClick}
                        onItemSelectionChange={selectable ? this.handleItemSelectionChange : undefined}
                        onPageChange={this.handlePageChange}
                        onSort={this.handleSort}
                        page={store.getPage()}
                        pageCount={store.pageCount}
                        schema={store.schema}
                        sortColumn={store.sortColumn.get()}
                        sortOrder={store.sortOrder.get()}
                        selections={store.selectionIds}
                    />
                </div>
            </Fragment>
        );
    }
}
