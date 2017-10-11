// @flow
import {observer} from 'mobx-react';
import React from 'react';
import equal from 'fast-deep-equal';
import Loader from '../../components/Loader';
import Pagination from '../../components/Pagination';
import DatagridStore from './stores/DatagridStore';
import datagridStyles from './datagrid.scss';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';

type Props = {
    onItemClick?: (rowId: string | number) => void,
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

    getAdapter(name: string = this.props.views[0]) {
        return datagridAdapterRegistry.get(name);
    }

    handleChangePage = (page: number) => {
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
        const Adapter = this.getAdapter();

        return (
            <section>
                <div className={datagridStyles.content}>
                    {this.props.store.loading
                        ? <Loader />
                        : <Adapter
                            data={store.data}
                            selections={store.selections}
                            schema={store.getFields()}
                            onItemClick={onItemClick}
                            onItemSelectionChange={this.handleItemSelectionChange}
                            onAllSelectionChange={this.handleAllSelectionChange}
                        />
                    }
                </div>
                {!!page && !!pageCount &&
                    <Pagination
                        current={page}
                        total={pageCount}
                        onChange={this.handleChangePage}
                    />
                }
            </section>
        );
    }
}
