// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader';
import Pagination from '../../components/Pagination';
import DatagridStore from './stores/DatagridStore';
import datagridStyles from './datagrid.scss';
import TableAdapter from './adapters/TableAdapter';

type Props = {
    onRowEditClick?: (rowId: string | number) => void,
    store: DatagridStore,
};

@observer
export default class Datagrid extends React.PureComponent<Props> {
    handleChangePage = (page: number) => {
        this.props.store.setPage(page);
    };

    render() {
        const {onRowEditClick, store} = this.props;
        const page = store.getPage();
        const pageCount = store.pageCount;

        return (
            <section>
                <div className={datagridStyles.content}>
                    {this.props.store.isLoading
                        ? <Loader />
                        : <TableAdapter
                            data={store.data}
                            schema={store.getFields()}
                            onRowEditClick={onRowEditClick}
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
