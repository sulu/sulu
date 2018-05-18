//@flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import {Dialog, Icon} from 'sulu-admin-bundle/components';
import CollectionFormOverlay from './CollectionFormOverlay';
import CollectionBreadcrumb from './CollectionBreadcrumb';
import type {OperationType, OverlayType} from './types';
import collectionSectionStyles from './collectionSection.scss';

const COLLECTIONS_RESOURCE_KEY = 'collections';

type Props = {
    datagridStore: DatagridStore,
    locale: IObservableValue<string>,
    onCollectionNavigate: (collectionId: ?string | number) => void,
    overlayType: OverlayType,
    resourceStore: ResourceStore,
};

@observer
export default class CollectionSection extends React.Component<Props> {
    @observable openedCollectionOperationOverlayType: OperationType;

    @action openCollectionOperationOverlay(operationType: OperationType) {
        this.openedCollectionOperationOverlayType = operationType;
    }

    @action closeCollectionOperationOverlay() {
        this.openedCollectionOperationOverlayType = null;
    }

    @computed get collectionId(): ?number | string {
        const {resourceStore} = this.props;
        return resourceStore.id;
    }

    @computed get resourceStoreByOperationType(): ResourceStore {
        const {resourceStore, locale} = this.props;
        const {data} = resourceStore;

        if (this.openedCollectionOperationOverlayType === 'update') {
            return resourceStore.clone();
        }

        return new ResourceStore(
            COLLECTIONS_RESOURCE_KEY,
            null,
            {
                locale,
            },
            {
                depth: 1,
                breadcrumb: true,
                parent: data.parent,
            }
        );
    }

    handleCollectionClick = (collectionId: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleBreadcrumbNavigate = (collectionId?: string | number) => {
        this.props.onCollectionNavigate(collectionId);
    };

    handleAddCollectionClick = () => {
        this.openCollectionOperationOverlay('create');
    };

    handleEditCollectionClick = () => {
        this.openCollectionOperationOverlay('update');
    };

    handleRemoveCollectionClick = () => {
        this.openCollectionOperationOverlay('remove');
    };

    handleCollectionOverlayConfirm = (resourceStore: ResourceStore) => {
        const options = {};
        options.breadcrumb = true;

        if (this.collectionId && this.openedCollectionOperationOverlayType === 'create') {
            options.parent = this.collectionId;
        }

        resourceStore.save(options)
            .then(() => this.handleSaveResponse(resourceStore));
    };

    handleSaveResponse = (resourceStore: ResourceStore) => {
        if (this.openedCollectionOperationOverlayType === 'update') {
            this.props.resourceStore.setMultiple(resourceStore.data);
        } else {
            this.props.onCollectionNavigate(resourceStore.id);
        }

        resourceStore.destroy();
        this.closeCollectionOperationOverlay();
    };

    handleCollectionOverlayClose = () => {
        this.closeCollectionOperationOverlay();
    };

    handleRemoveCollectionConfirm = () => {
        const {resourceStore} = this.props;

        resourceStore.delete()
            .then(() => {
                const {
                    data,
                } = resourceStore;

                this.closeCollectionOperationOverlay();
                this.props.onCollectionNavigate(
                    data._embedded && data._embedded.parent && data._embedded.parent.id
                        ? data._embedded.parent.id
                        : null
                );
            });
    };

    handleRemoveCollectionCancel = () => {
        this.closeCollectionOperationOverlay();
    };

    render() {
        const {
            overlayType,
            datagridStore,
            resourceStore,
        } = this.props;

        const operationType = this.openedCollectionOperationOverlayType;

        return (
            <div>
                {!resourceStore.loading &&
                    <div className={collectionSectionStyles.collectionSection}>
                        <CollectionBreadcrumb
                            onNavigate={this.handleBreadcrumbNavigate}
                            resourceStore={resourceStore}
                        />
                        <div>
                            <Icon name="su-plus" onClick={this.handleAddCollectionClick} />
                            {!!resourceStore.id &&
                                <Icon name="su-pen" onClick={this.handleEditCollectionClick} />
                            }
                            {!!resourceStore.id &&
                                <Icon name="su-trash-alt" onClick={this.handleRemoveCollectionClick} />
                            }
                        </div>
                    </div>
                }
                <Datagrid
                    adapters={['folder']}
                    onItemClick={this.handleCollectionClick}
                    store={datagridStore}
                />
                <CollectionFormOverlay
                    onClose={this.handleCollectionOverlayClose}
                    onConfirm={this.handleCollectionOverlayConfirm}
                    operationType={operationType}
                    overlayType={overlayType}
                    resourceStore={this.resourceStoreByOperationType}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={resourceStore.saving}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleRemoveCollectionCancel}
                    onConfirm={this.handleRemoveCollectionConfirm}
                    open={operationType === 'remove'}
                    title={translate('sulu_media.remove_collection')}
                >
                    {translate('sulu_media.remove_collection_warning')}
                </Dialog>
            </div>
        );
    }
}
