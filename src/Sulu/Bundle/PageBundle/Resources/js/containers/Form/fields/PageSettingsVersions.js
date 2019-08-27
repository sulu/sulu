// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {Dialog} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils';

type Props = FieldTypeProps<void>;

@observer
class PageSettingsVersions extends React.Component<Props> {
    listStore: ListStore;
    @observable page: IObservableValue<number> = observable.box(1);
    @observable restoreId: ?string | number = undefined;
    @observable restoring: boolean = false;

    constructor(props: Props) {
        super(props);

        const {formInspector} = this.props;

        this.listStore = new ListStore(
            'page_versions',
            'page_versions',
            'page_versions',
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
            .post('page_versions', {}, {action: 'restore', id, version: this.restoreId, locale, webspace})
            .then(action(() => {
                this.restoring = false;
                this.restoreId = undefined;
                if (!router) {
                    throw new Error('A router is required for this field type to work properly!');
                }
                router.navigate('sulu_page.page_edit_form', {id, locale, webspace});
            }));
    };

    render() {
        const actions = [
            {
                icon: 'su-process',
                onClick: this.handleRestoreClick,
            },
        ];

        return (
            <Fragment>
                <List
                    actions={actions}
                    adapters={['table']}
                    searchable={false}
                    selectable={false}
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

export default PageSettingsVersions;
