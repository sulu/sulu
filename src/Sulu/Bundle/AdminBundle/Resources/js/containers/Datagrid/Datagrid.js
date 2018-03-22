// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React from 'react';
import equal from 'fast-deep-equal';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';
import datagridStyles from './datagrid.scss';

type Props = {
    onItemClick?: (itemId: string | number) => void,
    onAddClick?: (id: string | number) => void,
    store: DatagridStore,
    adapters: Array<string>,
};

@observer
export default class Datagrid extends React.Component<Props> {
    @observable currentAdapterKey: string;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return datagridAdapterRegistry.get(this.currentAdapterKey);
    }

    componentWillMount() {
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
            store,
            onItemClick,
            onAddClick,
            adapters,
        } = this.props;
        const Adapter = this.currentAdapter;

        return (
            <div className={datagridStyles.datagrid}>
                <AdapterSwitch
                    adapters={adapters}
                    currentAdapter={this.currentAdapterKey}
                    onAdapterChange={this.handleAdapterChange}
                />
                <Adapter
                    active={store.active}
                    data={store.data}
                    loading={store.loading}
                    onAllSelectionChange={this.handleAllSelectionChange}
                    onItemActivation={this.handleItemActivation}
                    onItemClick={onItemClick}
                    onItemSelectionChange={this.handleItemSelectionChange}
                    onAddClick={onAddClick}
                    onPageChange={this.handlePageChange}
                    page={store.getPage()}
                    pageCount={store.pageCount}
                    schema={store.schema}
                    selections={store.selectionIds}
                />
            </div>
        );
    }
}
