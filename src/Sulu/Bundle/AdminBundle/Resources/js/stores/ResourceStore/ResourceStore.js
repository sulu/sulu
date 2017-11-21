// @flow
import {action, autorun, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';
import type {ObservableOptions, Schema} from './types';

export default class ResourceStore {
    resourceKey: string;
    id: string;
    observableOptions: ObservableOptions;
    disposer: () => void;
    @observable loading: boolean = false;
    @observable saving: boolean = false;
    @observable data: Object = {};
    @observable dirty: boolean = false;

    constructor(resourceKey: string, id: string, observableOptions: ObservableOptions = {}) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.observableOptions = observableOptions;
        this.disposer = autorun(this.load);
    }

    load = () => {
        const {locale} = this.observableOptions;
        const options = {};
        if (locale) {
            if (!locale.get()) {
                return;
            }
            options.locale = locale.get();
        }

        this.setLoading(true);
        ResourceRequester.get(this.resourceKey, this.id, options)
            .then(this.handleResponse);
    };

    @action handleResponse = (response: Object) => {
        this.data = response;
        this.setLoading(false);
    };

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setLocale(locale: string) {
        const {locale: observableLocale} = this.observableOptions;
        if (!observableLocale) {
            // TODO Should there really be a silent return, or should we throw an exception instead?
            return;
        }

        observableLocale.set(locale);
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

    @action changeSchema(schema: Schema) {
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

    get locale(): observable {
        return this.observableOptions.locale;
    }

    destroy() {
        this.disposer();
    }
}
