// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import Pagination from '../../../components/Pagination';
import {translate} from '../../../utils/Translator';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

export default @observer class FolderAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-folder';

    static defaultProps = {
        data: [],
    };

    static getInfoText(item: Object) {
        const label = (item.objectCount === 1)
            ? translate('sulu_admin.object')
            : translate('sulu_admin.objects');

        return `${item.objectCount} ${label}`;
    }

    render() {
        const {
            data,
            limit,
            loading,
            onItemClick,
            onLimitChange,
            onPageChange,
            page,
            pageCount,
        } = this.props;

        const folderList = (
            <FolderList onFolderClick={onItemClick}>
                {data.map((item: Object) => (
                    // TODO: Don't access properties like "title" directly.
                    <FolderList.Folder
                        id={item.id}
                        info={FolderAdapter.getInfoText(item)}
                        key={item.id}
                        title={item.title}
                    />
                ))}
            </FolderList>
        );

        if (page === 1 && data.length === 0) {
            return folderList;
        }

        return (
            <Pagination
                currentLimit={limit}
                currentPage={page}
                loading={loading}
                onLimitChange={onLimitChange}
                onPageChange={onPageChange}
                totalPages={pageCount}
            >
                {folderList}
            </Pagination>
        );
    }
}
