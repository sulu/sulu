// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {Dialog, Loader, Table} from 'sulu-admin-bundle/components';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import mediaHistoryStyles from './mediaHistory.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';

type Props = ViewProps & {
    resourceStore: ResourceStore,
    title?: string,
};

@observer
class MediaHistory extends React.Component<Props> {
    @observable deleteId: ?string | number = undefined;
    @observable deleting: boolean = false;
    showSuccess: IObservableValue<boolean> = observable.box(false);

    constructor(props: Props) {
        super(props);

        const {
            router,
            resourceStore,
        } = this.props;

        const locale = resourceStore.locale;

        if (!locale) {
            throw new Error('The resourceStore for the MediaHistory must have a locale');
        }

        router.bind('locale', locale);
    }

    @computed get versions(): Array<Object> {
        return Object.values(this.props.resourceStore.data.versions);
    }

    handleShowClick = (id: string | number) => {
        const version = this.versions.find((version) => version.version === id);
        if (!version) {
            throw new Error('Version "' + id + '" was not found. This should not happen and is likely a bug.');
        }

        window.open(version.url + '&inline=1');
    };

    @action handleDeleteClick = (version: string | number) => {
        this.deleteId = version;
    };

    @action handleDeleteCancel = () => {
        this.deleteId = undefined;
    };

    @action handleDeleteConfirm = () => {
        if (!this.deleteId) {
            throw new Error('The "deleteId" is not set. This should not happen and is likely a bug.');
        }

        const {resourceStore} = this.props;
        const {id, locale} = resourceStore;

        this.deleting = true;
        ResourceRequester.delete('media_versions', {id, locale, version: this.deleteId})
            .then(action(() => {
                this.deleting = false;
                this.deleteId = undefined;
                this.showSuccess.set(true);
                resourceStore.reload();
            }));
    };

    render() {
        const {resourceStore, title} = this.props;

        const viewButton = {
            icon: 'su-eye',
            onClick: this.handleShowClick,
        };

        const deleteButton = {
            icon: 'su-trash-alt',
            onClick: this.handleDeleteClick,
        };

        return (
            <Fragment>
                <div className={mediaHistoryStyles.mediaHistory}>
                    {title && <h1>{title}</h1>}
                    {resourceStore.loading
                        ? <Loader />
                        : <Table>
                            <Table.Header buttons={[viewButton, deleteButton]}>
                                <Table.HeaderCell>{translate('sulu_media.version')}</Table.HeaderCell>
                                <Table.HeaderCell>{translate('sulu_admin.created')}</Table.HeaderCell>
                            </Table.Header>
                            <Table.Body>
                                {this.versions.reverse().map((version: Object) => (
                                    <Table.Row
                                        buttons={[
                                            viewButton,
                                            {...deleteButton, visible: version.version !== resourceStore.data.version},
                                        ]}
                                        id={version.version}
                                        key={version.version}
                                    >
                                        <Table.Cell>{translate('sulu_media.version')} {version.version}</Table.Cell>
                                        <Table.Cell>{(new Date(version.created)).toLocaleString()}</Table.Cell>
                                    </Table.Row>
                                ))}
                            </Table.Body>
                        </Table>
                    }
                </div>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteCancel}
                    onConfirm={this.handleDeleteConfirm}
                    open={!!this.deleteId}
                    title={translate('sulu_admin.delete_warning_title')}
                >
                    {translate('sulu_admin.delete_warning_text')}
                </Dialog>
            </Fragment>
        );
    }
}

export default withToolbar(MediaHistory, function() {
    const {resourceStore, router} = this.props;
    const {locales} = router.route.options;
    const locale = locales
        ? {
            value: resourceStore.locale.get(),
            onChange: (locale) => {
                router.navigate(router.route.name, {...router.attributes, locale});
            },
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        backButton: {
            onClick: () => {
                router.restore(COLLECTION_ROUTE, {locale: resourceStore.locale.get()});
            },
        },
        showSuccess: this.showSuccess,
    };
});
