// @flow
import {observer} from 'mobx-react';
import React from 'react';
import equal from 'fast-deep-equal';
import Loader from '../../components/Loader';
import PaginationDecorator from './PaginationDecorator';
import DatagridStore from './stores/DatagridStore';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import type {AdapterConfig} from './types';

const INFINITE_SCROLL_TYPE = 'infiniteScroll';

type Props = {
    onItemClick?: (itemId: string | number) => void,
    store: DatagridStore,
    views: Array<string>,
};

@observer
export default class Datagrid extends React.PureComponent<Props> {
    componentWillMount() {
        this.validateViews();
    }

    componentWillReceiveProps(nextProps: Props) {
        if (!equal(this.props.views, nextProps.views)) {
            this.validateViews();
        }
    }

    validateViews() {
        this.props.views.forEach((viewName) => {
            if (!datagridAdapterRegistry.has(viewName)) {
                throw new Error(
                    'Datagrid-View with the name "' + viewName + '" does not exist.' +
                    'Did you forget to add it to the "datagridAdapterRegistry"?'
                );
            }
        });
    }

    getAdapterConfig(name: string = this.props.views[0]): AdapterConfig {
        return datagridAdapterRegistry.get(name);
    }

    hasScrollPagination() {
        const {paginationType} = this.getAdapterConfig();

        return paginationType === INFINITE_SCROLL_TYPE;
    }

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

    render() {
        const {
            store,
            onItemClick,
        } = this.props;
        const page = store.getPage();
        const pageCount = store.pageCount;
        const {
            Adapter,
            paginationType,
        } = this.getAdapterConfig();
        return (
            <div>
                {this.props.store.loading && !this.props.store.appendRequestData
                    ? <Loader />
                    : <PaginationDecorator
                        type={paginationType}
                        total={pageCount}
                        current={page}
                        loading={this.props.store.loading}
                        onChange={this.handlePageChange}
                    >
                        <Adapter
                            data={store.data}
                            selections={store.selections}
                            schema={store.getFields()}
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
