// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Dialog, Loader, Table} from 'sulu-admin-bundle/components';
import {SingleListOverlay, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import SnippetAreaStore from './stores/SnippetAreaStore';
import snippetAreasStyles from './snippetAreas.scss';

@observer
class SnippetAreas extends React.Component<ViewProps> {
    @observable openedAreaKey: ?string = undefined;
    snippetAreaStore: SnippetAreaStore;
    @observable deleteAreaKey: ?string = undefined;

    constructor(props: ViewProps) {
        super(props);

        const {router} = this.props;
        const {
            attributes: {
                webspace,
            },
        } = router;

        this.snippetAreaStore = new SnippetAreaStore(webspace);
    }

    @action handleAddClick = (areaKey: string) => {
        this.openedAreaKey = areaKey;
    };

    @action handleListOverlayClose = () => {
        this.openedAreaKey = undefined;
    };

    @action handleListOverlayConfirm = (snippet: Object) => {
        if (!this.openedAreaKey) {
            throw new Error(
                'The snippet area for saving has not been defined! This should not happen and is likely a bug.'
            );
        }

        this.snippetAreaStore.save(this.openedAreaKey, snippet.id).then(action(() => {
            this.openedAreaKey = undefined;
        }));
    };

    @action handleDeleteClick = (areaKey: string) => {
        this.deleteAreaKey = areaKey;
    };

    handleDeleteDialogConfirm = () => {
        if (!this.deleteAreaKey) {
            throw new Error('The area to delete has not been set! This should not happen and is likely a bug.');
        }

        this.snippetAreaStore.delete(this.deleteAreaKey).then(action(() => {
            this.deleteAreaKey = undefined;
        }));
    };

    @action handleDeleteDialogCancel = () => {
        this.deleteAreaKey = undefined;
    };

    render() {
        if (this.snippetAreaStore.loading) {
            return <Loader />;
        }

        return (
            <Fragment>
                <Table skin="light">
                    <Table.Header>
                        <Table.HeaderCell>{translate('sulu_snippet.snippet_area')}</Table.HeaderCell>
                        <Table.HeaderCell>{translate('sulu_snippet.snippet')}</Table.HeaderCell>
                    </Table.Header>
                    <Table.Body>
                        {Object.keys(this.snippetAreaStore.snippetAreas).map((areaKey) => {
                            const {defaultTitle, defaultUuid, key, title} = this.snippetAreaStore.snippetAreas[areaKey];

                            return (
                                <Table.Row key={key}>
                                    <Table.Cell>
                                        {title}
                                    </Table.Cell>
                                    <Table.Cell>
                                        {defaultUuid
                                            ? <Fragment>
                                                <div className={snippetAreasStyles.title}>
                                                    {defaultTitle}
                                                </div>
                                                <Button
                                                    className={snippetAreasStyles.deleteButton}
                                                    icon="su-trash-alt"
                                                    onClick={this.handleDeleteClick}
                                                    skin="link"
                                                    value={key}
                                                />
                                            </Fragment>
                                            : <Button
                                                className={snippetAreasStyles.addButton}
                                                icon="su-plus-circle"
                                                onClick={this.handleAddClick}
                                                skin="link"
                                                value={key}
                                            />
                                        }
                                    </Table.Cell>
                                </Table.Row>
                            );
                        })}
                    </Table.Body>
                </Table>
                <SingleListOverlay
                    adapter="table"
                    confirmLoading={this.snippetAreaStore.saving}
                    key={this.openedAreaKey}
                    listKey="snippets"
                    onClose={this.handleListOverlayClose}
                    onConfirm={this.handleListOverlayConfirm}
                    open={!!this.openedAreaKey}
                    options={{type: this.openedAreaKey}}
                    resourceKey="snippets"
                    title={translate('sulu_snippet.selection_overlay_title')}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.snippetAreaStore.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteDialogCancel}
                    onConfirm={this.handleDeleteDialogConfirm}
                    open={!!this.deleteAreaKey}
                    title={translate('sulu_admin.delete_warning_title')}
                >
                    {translate('sulu_admin.delete_warning_text')}
                </Dialog>
            </Fragment>
        );
    }
}

export default withToolbar(SnippetAreas, function() {
    return {};
});
