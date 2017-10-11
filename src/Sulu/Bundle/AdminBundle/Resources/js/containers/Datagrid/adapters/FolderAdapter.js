// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import type {DatagridAdapterProps} from '../types';
import {translate} from '../../../services/Translator';
import Pagination from '../../../components/Pagination';
import folderAdapterStyles from './folderAdapter.scss';

@observer
export default class FolderAdapter extends React.Component<DatagridAdapterProps> {
    static defaultProps = {
        data: [],
    };

    static getInfoText(item: Object) {
        const label = (item.objectCount === 1)
            ? translate('sulu_admin.object')
            : translate('sulu_admin.objects');

        return `${item.objectCount} ${label}`;
    }

    handlePageChange = (page: number) => {
        this.props.onPageChange(page);
    };

    render() {
        const {
            data,
            pageCount,
            currentPage,
            onItemClick,
        } = this.props;

        return (
            <div>
                <div className={folderAdapterStyles.adapter}>
                    <FolderList onFolderClick={onItemClick}>
                        {data.map((item: Object) => (
                            // TODO: Don't access properties like "title" directly.
                            <FolderList.Folder
                                key={item.id}
                                id={item.id}
                                title={item.title}
                                info={FolderAdapter.getInfoText(item)}
                            />
                        ))}
                    </FolderList>
                </div>
                {!!currentPage && !!pageCount &&
                    <Pagination
                        total={pageCount}
                        current={currentPage}
                        onChange={this.handlePageChange}
                    />
                }
            </div>
        );
    }
}
