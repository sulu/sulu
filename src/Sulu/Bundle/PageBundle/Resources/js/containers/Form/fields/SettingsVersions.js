// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Dialog} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import log from 'loglevel';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = FieldTypeProps<void>;

@observer
class SettingsVersions extends React.Component<Props> {
    listStore: ListStore;
    @observable page: IObservableValue<number> = observable.box(1);
    @observable restoreId: ?string | number = undefined;
    @observable restoring: boolean = false;

    constructor(props: Props) {
        super(props);

        // @deprecated
        log.warn(
            'The "SettingsVersions" field-type is deprecated since 2.3 and will be removed. ' +
            'Use a list view with the the "RestoreVersionItemAction" to restore previous versions instead.'
        );

        const {formInspector} = this.props;

        this.listStore = new ListStore(
            this.resourceKey,
            this.listKey,
            this.userSettingsKey,
            {locale: formInspector.locale, page: this.page},
            {id: formInspector.id, webspace: formInspector.options.webspace}
        );

        formInspector.addSaveHandler((action) => {
            if (action !== 'publish') {
                return;
            }

            this.listStore.reload();
        });
    }

    @computed get resourceKey(): string {
        const {
            schemaOptions: {
                resource_key: {
                    value: resourceKey,
                } = {},
            },
        } = this.props;

        if (resourceKey === undefined || typeof resourceKey !== 'string') {
            throw new Error(
                'The "resource_key" schemaOption is mandatory and must be a string, but received ' +
                typeof resourceKey + '!'
            );
        }

        return resourceKey;
    }

    @computed get listKey(): string {
        const {
            schemaOptions: {
                list_key: {
                    value: listKey = this.resourceKey,
                } = {},
            },
        } = this.props;

        if (typeof listKey !== 'string') {
            throw new Error(
                'The "list_key" schemaOption must be a string, but received ' +
                typeof listKey + '!'
            );
        }

        return listKey;
    }

    @computed get userSettingsKey(): string {
        const {
            schemaOptions: {
                user_settings_key: {
                    value: userSettingsKey = this.listKey,
                } = {},
            },
        } = this.props;

        if (typeof userSettingsKey !== 'string') {
            throw new Error(
                'The "user_settings_key" schemaOption must be a string, but received ' +
                typeof userSettingsKey + '!'
            );
        }

        return userSettingsKey;
    }

    @computed get parentRoute(): string {
        const {router} = this.props;

        if (!router?.route?.parent?.name) {
            throw new Error(
                'A route with a valid parent route is required for this field type to work properly!'
            );
        }

        return router.route.parent.name;
    }

    @action handleRestoreClick = (id: string | number) => {
        this.restoreId = id;
    };

    @action handleCancel = () => {
        this.restoreId = undefined;
    };

    @action handleConfirm = () => {
        const {
            formInspector: {
                id,
                locale,
                options: {
                    webspace,
                },
            },
            router,
        } = this.props;

        this.restoring = true;
        ResourceRequester
            .post(this.resourceKey, {}, {action: 'restore', id, version: this.restoreId, locale, webspace})
            .then(action(() => {
                this.restoring = false;
                this.restoreId = undefined;
                if (!router) {
                    throw new Error('A router is required for this field type to work properly!');
                }
                router.navigate(this.parentRoute, {id, locale, webspace});
            }));
    };

    getListItemActions = () => {
        return [
            {
                icon: 'su-process',
                onClick: this.handleRestoreClick,
            },
        ];
    };

    render() {
        return (
            <Fragment>
                <List
                    adapters={['table']}
                    filterable={false}
                    itemActionsProvider={this.getListItemActions}
                    searchable={false}
                    selectable={false}
                    showColumnOptions={false}
                    store={this.listStore}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.restoring}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCancel}
                    onConfirm={this.handleConfirm}
                    open={!!this.restoreId}
                    title={translate('sulu_page.restore_version')}
                >
                    {translate('sulu_page.restore_version_text')}
                </Dialog>
            </Fragment>
        );
    }
}

export default SettingsVersions;
