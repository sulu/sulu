// @flow
import {action, autorun, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {Schema} from '../types';

export default class FormStore {
    resourceKey: string;
    id: string;
    locale = observable();
    disposer: () => void;
    @observable loading: boolean = false;
    @observable saving: boolean = false;
    @observable data: Object = {};
    @observable dirty: boolean = false;

    constructor(resourceKey: string, id: string) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.disposer = autorun(this.load);
    }

    load = () => {
        if (!this.locale.get()) {
            return;
        }

        this.setLoading(true);
        ResourceRequester.get(this.resourceKey, this.id, {locale: this.locale.get()})
            .then(this.handleResponse);
    };

    @action handleResponse = (response) => {
        this.data = response;
        this.setLoading(false);
    };

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setLocale(locale: string) {
        this.locale.set(locale);
    }

    @action save() {
        this.saving = true;
        ResourceRequester.put(this.resourceKey, this.id, this.data, {locale: this.locale.get()})
            .then(action((response) => {
                this.data = response;
                this.saving = false;
                this.dirty = false;
            }))
            .catch(action(() => {
                this.saving = false;
            }));
    }

    changeSchema(schema: Schema) {
        const schemaFields = Object.keys(schema).reduce((object, key) => {
            object[key] = null;
            return object;
        }, {});

        this.data = {...schemaFields, ...this.data};
    }

    @action set(name: string, value: mixed) {
        this.data[name] = value;
        this.dirty = true;
    }

    destroy() {
        this.disposer();
    }
}
