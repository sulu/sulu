// @flow
import {observer} from 'mobx-react';
import {observable, action, computed} from 'mobx';
import React from 'react';
import equal from 'fast-deep-equal';
import InfiniteScroller from '../../components/InfiniteScroller';
import Pagination from '../../components/Pagination';
import {translate} from '../../services/Translator';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';
import AdapterSwitch from './AdapterSwitch';

type Props = {
    onItemClick?: (itemId: string | number) => void,
    store: DatagridStore,
    adapters: Array<string>,
};

@observer
export default class Datagrid extends React.PureComponent<Props> {
    @observable currentAdapterKey: string;

    @computed get currentAdapter(): typeof AbstractAdapter {
        return datagridAdapterRegistry.get(this.currentAdapterKey);
    }

    componentWillMount() {
        this.validateAdapters();
        this.props.store.init(this.currentAdapter.getLoadingStrategy());
    }

    componentWillReceiveProps(nextProps: Props) {
        if (!equal(this.props.adapters, nextProps.adapters)) {
            this.validateAdapters();
        }
        nextProps.store.init(this.currentAdapter.getLoadingStrategy());
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
    };

    handlePageChange = (page: number) => {
        this.props.store.setPage(page);
    };

    handleItemSelectionChange = (id: string | number, selected?: boolean) => {
        const {store} = this.props;
        selected ? store.select(id) : store.deselect(id);
    };

    handleAllSelectionChange = (selected?: boolean) => {
        const {store} = this.props;
        selected ? store.selectEntirePage() : store.deselectEntirePage();
    };

    handleAdapterChange = (adapter: string) => {
        this.setCurrentAdapterKey(adapter);
        this.props.store.updateLoadingStrategy(this.currentAdapter.getLoadingStrategy());
    };

    render() {
        const {
            store,
            onItemClick,
            adapters,
        } = this.props;
        const page = store.getPage();
        const pageCount = store.pageCount;
        const Adapter = this.currentAdapter;
        const PaginationAdapter = Adapter.getLoadingStrategy() === 'infiniteScroll' ? InfiniteScroller : Pagination;

        return (
            <div>
                <AdapterSwitch
                    adapters={adapters}
                    currentAdapter={this.currentAdapterKey}
                    onAdapterChange={this.handleAdapterChange}
                />
                <PaginationAdapter
                    type={Adapter.getLoadingStrategy()}
                    total={pageCount}
                    current={page}
                    loading={this.props.store.loading}
                    onChange={this.handlePageChange}
                >
                    <Adapter
                        data={store.data}
                        selections={store.selections}
                        schema={store.getSchema()}
                        onItemClick={onItemClick}
                        onItemSelectionChange={this.handleItemSelectionChange}
                        onAllSelectionChange={this.handleAllSelectionChange}
                    />
                </PaginationAdapter>
            </div>
        );
    }
}
