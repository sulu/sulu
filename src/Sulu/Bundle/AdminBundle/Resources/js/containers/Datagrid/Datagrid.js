// @flow
import {observer} from 'mobx-react';
import {observable, action} from 'mobx';
import React from 'react';
import equal from 'fast-deep-equal';
import Loader from '../../components/Loader';
import PaginationDecorator from './PaginationDecorator';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import AbstractAdapter from './adapters/AbstractAdapter';

type Props = {
    onItemClick?: (itemId: string | number) => void,
    store: DatagridStore,
    adapters: Array<string>,
};

type CurrentAdapter = {
    adapter: typeof AbstractAdapter,
    key: string,
}

@observer
export default class Datagrid extends React.PureComponent<Props> {
    @observable currentAdapter: CurrentAdapter;

    componentWillMount() {
        this.validateAdapters();
    }

    componentWillReceiveProps(nextProps: Props) {
        if (!equal(this.props.adapters, nextProps.adapters)) {
            this.validateAdapters();
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

        if (!this.currentAdapter) {
            this.setCurrentAdapter(adapters[0]);
        }
    }

    @action setCurrentAdapter = (adapter: string) => {
        this.currentAdapter = {
            adapter: datagridAdapterRegistry.get(adapter),
            key: adapter,
        };
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
        this.setCurrentAdapter(adapter);
        this.props.store.updateLoadingStrategy(this.currentAdapter.adapter.getLoadingStrategy());
    };

    render() {
        const {
            store,
            onItemClick,
            adapters,
        } = this.props;
        const page = store.getPage();
        const pageCount = store.pageCount;
        const Adapter = this.currentAdapter.adapter;

        return (
            <div>
                <ul>
                    {adapters.map((adapter, index) => {
                        const handleClick = () => {
                            this.handleAdapterChange(adapter);
                        };

                        return (
                            <li key={index} onClick={handleClick}>
                                {adapter}
                            </li>
                        );
                    })
                    }
                </ul>
                {this.props.store.loading && !this.props.store.appendRequestData
                    ? <Loader />
                    : <PaginationDecorator
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
                    </PaginationDecorator>
                }
            </div>
        );
    }
}
