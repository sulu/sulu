// @flow
import {action, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {SnippetArea} from '../types';

export default class SnippetAreaStore {
    @observable snippetAreas: {[key: string]: SnippetArea} = {};
    @observable loading: boolean = true;
    @observable forbidden: boolean = false;
    @observable unexpectedError: boolean = false;
    @observable saving: boolean = false;
    @observable deleting: boolean = false;
    webspaceKey: string;

    constructor(webspaceKey: string) {
        this.webspaceKey = webspaceKey;

        ResourceRequester.getList('snippet_areas', {webspaceKey}).then(action((response) => {
            this.snippetAreas = response._embedded.snippet_areas.reduce((snippetAreas, snippetArea) => {
                snippetAreas[snippetArea.key] = snippetArea;

                return snippetAreas;
            }, {});
            this.forbidden = false;
            this.unexpectedError = false;
            this.loading = false;
        })).catch(action((response: Object) => {
            if (response.status === 403) {
                this.forbidden = true;
                this.loading = false;

                return;
            }

            this.unexpectedError = true;
            this.loading = false;
        }));
    }

    @action save(areaKey: string, snippetUuid: string) {
        this.saving = true;

        return ResourceRequester.put('snippet_areas', {snippetUuid}, {key: areaKey, webspaceKey: this.webspaceKey})
            .then(action((response) => {
                this.snippetAreas[areaKey] = response;
                this.saving = false;
            }));
    }

    @action delete(areaKey: string) {
        this.deleting = true;

        return ResourceRequester.delete('snippet_areas', {key: areaKey, webspaceKey: this.webspaceKey})
            .then(action((response) => {
                this.snippetAreas[areaKey] = response;
                this.deleting = false;
            }));
    }
}
