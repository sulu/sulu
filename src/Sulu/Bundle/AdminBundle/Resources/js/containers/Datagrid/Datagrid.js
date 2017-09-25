// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader';
import Pagination from '../../components/Pagination';
import DatagridStore from './stores/DatagridStore';
import datagridStyles from './datagrid.scss';
import adapterStore from './stores/AdapterStore';

type Props = {
    onItemEditClick?: (rowId: string | number) => void,
    store: DatagridStore,
    views: Array<string>,
};

@observer
export default class Datagrid extends React.PureComponent<Props> {
    getAdapter(name: string = this.props.views[0]) {
        return adapterStore.get(name);
    }

    handleChangePage = (page: number) => {
        this.props.store.setPage(page);
    };

    handleRowSelectionChange = (id: string | number, selected?: boolean) => {
        const {store} = this.props;
        selected ? store.select(id) : store.deselect(id);
    };

    handleAllSelectionChange = (selected?: boolean) => {
        const {store} = this.props;
        selected ? store.selectEntirePage() : store.deselectEntirePage();
    };

    render() {
        const {onItemEditClick, store} = this.props;
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
                            onItemEditClick={onItemEditClick}
                            onItemSelectionChange={this.handleRowSelectionChange}
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
