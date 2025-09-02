// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import Dialog from 'sulu-admin-bundle/components/Dialog';
import Loader from 'sulu-admin-bundle/components/Loader';
import Overlay from 'sulu-admin-bundle/components/Overlay';
import Table from 'sulu-admin-bundle/components/Table';
import ResourceListStore from 'sulu-admin-bundle/stores/ResourceListStore';
import resourceLocatorHistoryStyles from './resourceLocatorHistory.scss';

type Props = {|
    id: ?string | number,
    options: Object,
    resourceKey: string,
|};

@observer
class ResourceLocatorHistory extends React.Component<Props> {
    resourceListStore: ?ResourceListStore;
    @observable open = false;
    @observable showDeleteWarning = false;
    deleteId: ?string | number;

    @action handleButtonClick = () => {
        const {id, options, resourceKey} = this.props;
        this.resourceListStore = new ResourceListStore(resourceKey, {...options, id});
        this.open = true;
    };

    @action handleOverlayConfirm = () => {
        this.open = false;
    };

    @action handleOverlayClose = () => {
        this.open = false;
    };

    @action handleDeleteClick = (id: string | number) => {
        this.showDeleteWarning = true;
        this.deleteId = id;
    };

    @action handleDeleteCancel = () => {
        this.showDeleteWarning = false;
        this.deleteId = undefined;
    };

    @action handleDeleteConfirm = () => {
        if (!this.deleteId) {
            throw new Error('The "deleteId" has not been set! This should not happen and is likely a bug!');
        }

        if (!this.resourceListStore) {
            throw new Error(
                'The ResourceListStore has not been initialized yet! This should not happen and is likely a bug.'
            );
        }

        this.resourceListStore.deleteList([this.deleteId]).then(action(() => {
            this.showDeleteWarning = false;
            this.deleteId = undefined;
        }));
    };

    render() {
        const {resourceListStore} = this;

        const historyRoutes = resourceListStore ? resourceListStore.data : [];

        return (
            <Fragment>
                {/* TODO Sulu 3.0 does not yet support the Show History controller therefore we disable the button */}
                {/*<Button disabled={!id} icon="su-process" onClick={this.handleButtonClick} skin="link">*/}
                {/*    {translate('sulu_admin.show_history')}*/}
                {/*</Button>*/}
                <Overlay
                    confirmText={translate('sulu_admin.ok')}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.open}
                    size="small"
                    title={translate('sulu_admin.history')}
                >
                    {!this.resourceListStore || this.resourceListStore.loading
                        ? <div className={resourceLocatorHistoryStyles.loader}>
                            <Loader />
                        </div>
                        : <div className={resourceLocatorHistoryStyles.resourceLocatorHistoryOverlay}>
                            <Table buttons={[{icon: 'su-trash-alt', onClick: this.handleDeleteClick}]}>
                                <Table.Header>
                                    <Table.HeaderCell>{translate('sulu_admin.url')}</Table.HeaderCell>
                                    <Table.HeaderCell>{translate('sulu_admin.created')}</Table.HeaderCell>
                                </Table.Header>
                                <Table.Body>
                                    {historyRoutes.map((historyRoute) => (
                                        <Table.Row id={historyRoute.id} key={historyRoute.id}>
                                            <Table.Cell>{historyRoute.resourcelocator}</Table.Cell>
                                            <Table.Cell>{(new Date(historyRoute.created)).toLocaleString()}</Table.Cell>
                                        </Table.Row>
                                    ))}
                                </Table.Body>
                            </Table>
                        </div>
                    }
                </Overlay>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={resourceListStore ? resourceListStore.deleting : false}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteCancel}
                    onConfirm={this.handleDeleteConfirm}
                    open={this.showDeleteWarning}
                    title={translate('sulu_admin.delete')}
                >
                    {translate('sulu_admin.resource_locator_history_delete_warning')}
                </Dialog>
            </Fragment>
        );
    }
}

export default ResourceLocatorHistory;
