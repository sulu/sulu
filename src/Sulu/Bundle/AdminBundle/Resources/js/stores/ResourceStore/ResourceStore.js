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
    idQueryParameter: ?string;
    preventLoadingOnce: boolean;

    constructor(
        resourceKey: string,
        id: ?string | number,
        observableOptions: ObservableOptions = {},
        loadOptions: Object = {},
        idQueryParameter: ?string,
        preventLoadingOnce: boolean = false
    ) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.observableOptions = observableOptions;
        this.loadOptions = loadOptions;
        this.idQueryParameter = idQueryParameter;
        this.preventLoadingOnce = preventLoadingOnce;
        this.disposer = autorun(this.load);
    }

    load = () => {
        const {
            id,
        } = this;
        const options = {};

        if (this.preventLoadingOnce) {
            this.preventLoadingOnce = false;
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

        const promise = this.idQueryParameter
            ? ResourceRequester.get(
                this.resourceKey,
                undefined,
                {...options, ...this.loadOptions, [this.idQueryParameter]: id}
            )
            : ResourceRequester.get(this.resourceKey, id, {...options, ...this.loadOptions});

        promise.then(this.handleResponse);
    };

    @action handleResponse = (response: Object) => {
        if (this.idQueryParameter) {
            this.handleIdQueryParameterResponse(response);
            this.data = {...this.data, ...response};
        } else {
            this.data = response;
        }

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

        if (this.idQueryParameter || !this.id) {
            return this.create(options);
        }

        return this.update(options);
    }

    @action create(options: Object): Promise<*> {
        this.saving = true;

        const requestOptions = options;

        if (this.idQueryParameter) {
            requestOptions[this.idQueryParameter] = this.id;
        }

        return ResourceRequester.post(this.resourceKey, this.data, requestOptions)
            .then(action((response) => {
                this.handleIdQueryParameterResponse(response);
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
        if (!this.data.id) {
            throw new Error('Cannot delete resource with an undefined "id"');
        }

        this.saving = true;

        return ResourceRequester.delete(this.resourceKey, this.data.id)
            .then(action((response) => {
                this.id = undefined;
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
        if (name === 'id' && (typeof value === 'string' || typeof value === 'number')) {
            this.id = value;
        }

        this.data[name] = value;
    }

    @action setMultiple(data: Object) {
        if (data.id) {
            this.id = data.id;
        }

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
            undefined,
            true
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

    @action handleIdQueryParameterResponse(response: Object) {
        if (response.id) {
            this.idQueryParameter = undefined;
            this.id = response.id;
            this.preventLoadingOnce = true;
        }
    }
}
