// @flow
import {observer} from 'mobx-react';
import React from 'react';
import FolderList from '../../../components/FolderList';
import Pagination from '../../../components/Pagination';
import {translate} from '../../../utils/Translator';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class FolderAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-folder';

    static defaultProps = {
        data: [],
    };

    static getInfoText(item: Object) {
        const label = (1 === item.objectCount)
            ? translate('sulu_admin.object')
            : translate('sulu_admin.objects');

        return `${item.objectCount} ${label}`;
    }

    render() {
        const {
            data,
            loading,
            onItemClick,
            onPageChange,
            page,
            pageCount,
        } = this.props;

        return (
            <Pagination
                total={pageCount}
                current={page}
                loading={loading}
                onChange={onPageChange}
            >
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
            </Pagination>
        );
    }
}
