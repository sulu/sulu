// @flow
import {action, autorun, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import ResourceRequester from '../../services/ResourceRequester';
import type {ObservableOptions} from './types';

export default class ResourceStore {
    resourceKey: string;
    @observable id: ?string | number;
    observableOptions: ObservableOptions;
    disposer: () => void;
    @observable loading: boolean = false;
    @observable saving: boolean = false;
    @observable data: Object = {};
    @observable dirty: boolean = false;
    loadOptions: Object = {};
    preventLoadingOnce: boolean;

    constructor(
        resourceKey: string,
        id: ?string | number,
        observableOptions: ObservableOptions = {},
        loadOptions: Object = {},
        preventLoadingOnce: boolean = true
    ) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.observableOptions = observableOptions;
        this.loadOptions = loadOptions;
        this.preventLoadingOnce = preventLoadingOnce;
        this.disposer = autorun(this.load);
    }

    load = () => {
        const id = this.id;
        const options = {};

        if (!this.preventLoadingOnce) {
            this.preventLoadingOnce = true;
            return;
        }

        if (!id) {
            return;
        }

        const {locale} = this.observableOptions;

        if (locale) {
            if (!locale.get()) {
                return;
            }
            options.locale = locale.get();
        }

        this.setLoading(true);
        ResourceRequester.get(this.resourceKey, id, {...options, ...this.loadOptions})
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

    @action save(options: Object = {}): Promise<*> {
        const {locale} = this.observableOptions;

        if (locale) {
            options.locale = locale.get();
        }

        if (!this.id) {
            return this.create(options);
        }

        return this.update(options);
    }

    @action create(options: Object): Promise<*> {
        this.saving = true;

        return ResourceRequester.post(this.resourceKey, this.data, options)
            .then(action((response) => {
                this.id = response.id;
                this.data = response;
                this.saving = false;
                this.dirty = false;
            }))
            .catch(action(() => {
                this.saving = false;
            }));
    }

    @action update(options: Object): Promise<*> {
        if (!this.id) {
            throw new Error('Can not save resource with an undefined "id"');
        }

        this.saving = true;

        return ResourceRequester.put(this.resourceKey, this.id, this.data, options)
            .then(action((response) => {
                this.data = response;
                this.saving = false;
                this.dirty = false;
            }))
            .catch(action(() => {
                this.saving = false;
            }));
    }

    @action delete(): Promise<*> {
        if (!this.id) {
            throw new Error('Can not delete resource with an undefined "id"');
        }

        this.saving = true;

        return ResourceRequester.delete(this.resourceKey, this.id)
            .then(action((response) => {
                this.data = response;
                this.saving = false;
                this.dirty = false;

                this.destroy();
            }))
            .catch(action(() => {
                this.saving = false;
            }));
    }

    @action set(name: string, value: mixed) {
        this.data[name] = value;
    }

    @action setMultiple(data: Object) {
        this.data = {...this.data, ...data};
    }

    @action change(name: string, value: mixed) {
        this.set(name, value);
        this.dirty = true;
    }

    @action changeMultiple(data: Object) {
        this.setMultiple(data);
        this.dirty = true;
    }

    @action clone() {
        const clonedResourceStore = new ResourceStore(
            this.resourceKey,
            this.id,
            this.observableOptions,
            this.loadOptions,
            false
        );

        clonedResourceStore.data = toJS(this.data);

        return clonedResourceStore;
    }

    get locale(): ?IObservableValue<string> {
        return this.observableOptions.locale;
    }

    destroy() {
        this.disposer();
    }
}
