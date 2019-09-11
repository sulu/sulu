// @flow
import {action, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';
import type {Collaboration} from './types';

export default class CollaborationStore {
    // TODO read this value from config
    static interval: number = 10000;

    resourceKey: string;
    id: string | number;
    destroyed: boolean = false;

    @observable collaborations: Array<Collaboration> = [];

    constructor(resourceKey: string, id: string | number) {
        this.resourceKey = resourceKey;
        this.id = id;

        this.sendRequest();
    }

    sendRequest() {
        if (this.destroyed) {
            return;
        }

        ResourceRequester.put('collaborations', null, {id: this.id, resourceKey: this.resourceKey})
            .then(action((response) => {
                this.collaborations.splice(0, this.collaborations.length);
                this.collaborations.push(...response._embedded.collaborations);
                setTimeout(() => this.sendRequest(), CollaborationStore.interval);
            }));
    }

    destroy() {
        this.destroyed = true;
        ResourceRequester.delete('collaborations', {id: this.id, resourceKey: this.resourceKey});
    }
}
