// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate, ResourceRequester} from 'sulu-admin-bundle/services';
import {withToolbar, Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import mediaOverviewStyles from './mediaOverview.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';
const COLLECTIONS_RESOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    page: observable = observable();
    locale: observable = observable();
    @observable title: string;
    @observable parentId: ?string | number;
    @observable collectionStore: DatagridStore;
    @observable collectionId: string | number;
    disposer: () => void;

    componentWillMount() {
        const {router} = this.props;

        router.bind('page', this.page, '1');
        router.bind('locale', this.locale);

        this.disposer = autorun(this.load);
    }

    componentWillUnmount() {
        const {router} = this.props;
        this.disposer();
        router.unbind('locale', this.page);
        router.unbind('page', this.locale);
        this.collectionStore.destroy();
    }

    load = () => {
        const {router} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;

        if (id) {
            this.loadCollectionInfo(id);
        }

        this.createCollectionStore(id);
    };

    loadCollectionInfo(collectionId) {
        return ResourceRequester.get(COLLECTIONS_RESOURCE_KEY, collectionId, {
            depth: 1,
            locale: this.locale,
        }).then(action((collectionInfo) => {
            const parentCollection = collectionInfo._embedded.parent;
            this.title = collectionInfo.title;
            this.parentId = (parentCollection) ? parentCollection.id : undefined;
        }));
    }

    getTitle() {
        if (!this.collectionId) {
            return translate('sulu_media.all_media');
        }

        return this.title;
    }

    @action createCollectionStore(collectionId) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionId = collectionId;
        this.collectionStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page: this.page,
                locale: this.locale,
            },
            (collectionId) ? {parent: collectionId} : undefined
        );
    }

    handleOpenFolder = (collectionId) => {
        const {router} = this.props;
        router.navigate(COLLECTION_ROUTE, {id: collectionId, page: '1', locale: this.locale.get()});
    };

    render() {
        return (
            <div className={mediaOverviewStyles.mediaOverview}>
                <h1>{this.getTitle()}</h1>
                <Datagrid
                    store={this.collectionStore}
                    views={['folder']}
                    onItemClick={this.handleOpenFolder}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;

    const {
        route: {
            options: {
                locales,
            },
        },
    } = this.props.router;

    const locale = locales
        ? {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        disableAll: this.collectionStore.loading,
        backButton: (this.collectionId !== undefined)
            ? {
                onClick: () => {
                    router.restore(COLLECTION_ROUTE, {id: this.parentId, locale: this.locale.get()});
                },
            }
            : undefined,
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
