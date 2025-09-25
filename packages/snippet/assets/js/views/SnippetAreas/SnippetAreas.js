// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Dialog, Loader, Table} from 'sulu-admin-bundle/components';
import {SingleListOverlay, withToolbar} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {CacheClearToolbarAction} from 'sulu-website-bundle/containers';
import SnippetAreaStore from './stores/SnippetAreaStore';
import snippetAreasStyles from './snippetAreas.scss';
import type {ViewProps} from 'sulu-admin-bundle/containers';

@observer
class SnippetAreas extends React.Component<ViewProps> {
    @observable openedAreaKey: ?string = undefined;
    snippetAreaStore: SnippetAreaStore;
    @observable deleteAreaKey: ?string = undefined;
    cacheClearToolbarAction: CacheClearToolbarAction;

    constructor(props: ViewProps) {
        super(props);

        const {router} = this.props;
        const {
            attributes: {
                webspace,
            },
        } = router;

        if (typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string!');
        }

        this.snippetAreaStore = new SnippetAreaStore(webspace);
        this.cacheClearToolbarAction = new CacheClearToolbarAction(webspace);
    }

    @action handleSnippetClick = (snippetUuid: string) => {
        const {router, route} = this.props;
        const {snippetEditView} = route.options;

        router.navigate(snippetEditView, {id: snippetUuid});
    };

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

        const {
            attributes: {
                locale,
            },
        } = this.props.router;

        return (
            <Fragment>
                <Table skin="light">
                    <Table.Header>
                        <Table.HeaderCell>{translate('sulu_snippet.snippet_area')}</Table.HeaderCell>
                        <Table.HeaderCell>{translate('sulu_snippet.snippet')}</Table.HeaderCell>
                    </Table.Header>
                    <Table.Body>
                        {Object.keys(this.snippetAreaStore.snippetAreas).map((areaKey) => {
                            const {snippetTitle, snippetUuid, key, title} = this.snippetAreaStore.snippetAreas[areaKey];

                            return (
                                <Table.Row key={key}>
                                    <Table.Cell>
                                        {title}
                                    </Table.Cell>
                                    <Table.Cell>
                                        {snippetUuid
                                            ? <Fragment>
                                                <Button
                                                    className={snippetAreasStyles.titleButton}
                                                    onClick={this.handleSnippetClick}
                                                    skin="text"
                                                    value={snippetUuid}
                                                >
                                                    {snippetTitle}
                                                </Button>
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
                    locale={observable.box(locale)}
                    onClose={this.handleListOverlayClose}
                    onConfirm={this.handleListOverlayConfirm}
                    open={!!this.openedAreaKey}
                    options={{areas: this.openedAreaKey}}
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
                {this.cacheClearToolbarAction.getNode()}
            </Fragment>
        );
    }
}

export default withToolbar(SnippetAreas, function() {
    return {
        items: [
            this.cacheClearToolbarAction.getToolbarItemConfig(),
        ],
    };
});
