// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import ResourceRequester from '../../services/ResourceRequester';
import type {ObservableOptions, Schema} from './types';

function addObjectProperty(schema: Schema, key, object) {
    const type = schema[key].type;

    if (type !== 'section') {
        object[key] = null;
    }

    const items = schema[key].items;

    if (type === 'section' && items) {
        Object.keys(items)
            .reduce((object, childKey) => addObjectProperty(items, childKey, object), object);
    }

    return object;
}

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
            throw new Error(
                '"setLocale" should not be called on a ResourceStore which got no locale passed in the constructor'
            );
        }

        observableLocale.set(locale);
    }

    @action save() {
        this.saving = true;
        const {locale} = this.observableOptions;
        const options = {};
        if (locale) {
            options.locale = locale.get();
        }
        ResourceRequester.put(this.resourceKey, this.id, this.data, options)
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
        const schemaFields = Object.keys(schema).reduce((object, key) => addObjectProperty(schema, key, object), {});

        this.data = {...schemaFields, ...this.data};
    }

    @action set(name: string, value: mixed) {
        this.data[name] = value;
        this.dirty = true;
    }

    get locale(): ?IObservableValue<string> {
        return this.observableOptions.locale;
    }

    destroy() {
        this.disposer();
    }
}
