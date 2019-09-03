// @flow
import {action, autorun, observable, toJS, set, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import log from 'loglevel';
import jsonpointer from 'json-pointer';
import ResourceRequester from '../../services/ResourceRequester';
import type {ObservableOptions} from './types';

export default class ResourceStore {
    resourceKey: string;
    @observable id: ?string | number;
    observableOptions: ObservableOptions;
    disposer: () => void;
    @observable initialized: boolean = false;
    @observable loading: boolean = false;
    @observable saving: boolean = false;
    @observable deleting: boolean = false;
    @observable moving: boolean = false;
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
            observableOptions: {
                locale,
            },
        } = this;

        if (locale && !locale.get()) {
            return;
        }

        if (this.preventLoadingOnce) {
            this.preventLoadingOnce = false;
            return;
        }

        if (!id) {
            this.initialized = true;
            return;
        }

        const options = {};
        if (locale) {
            options.locale = locale.get();
        }

        log.info('ResourceStore loads "' + this.resourceKey + '" data with the ID "' + id + '"');

        this.setLoading(true);
        const promise = this.idQueryParameter
            ? ResourceRequester.get(
                this.resourceKey,
                {...options, ...this.loadOptions, [this.idQueryParameter]: id}
            )
            : ResourceRequester.get(this.resourceKey, {...options, ...this.loadOptions, id});

        promise.then(action((response: Object) => {
            if (this.idQueryParameter) {
                this.handleIdQueryParameterResponse(response);
                this.setMultiple(response);
            } else {
                this.setMultiple(response);
            }

            this.initialized = true;
            this.setLoading(false);
            this.dirty = false;
        }));
    };

    @action reload = () => {
        this.load();
    };

    @action setLoading(loading: boolean) {
        this.loading = loading;
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
                this.setMultiple(response);
                this.saving = false;
                this.dirty = false;

                return response;
            }))
            .catch(action((error) => {
                this.saving = false;
                throw error;
            }));
    }

    @action update(options: Object): Promise<*> {
        if (!this.id) {
            throw new Error('Can not save resource with an undefined "id"');
        }

        this.saving = true;

        return ResourceRequester.put(this.resourceKey, this.data, {...options, id: this.id})
            .then(action((response) => {
                this.setMultiple(response);
                this.saving = false;
                this.dirty = false;

                return response;
            }))
            .catch(action((error) => {
                this.saving = false;
                throw error;
            }));
    }

    @action delete(options: Object = {}): Promise<*> {
        if (!this.data.id) {
            throw new Error('Cannot delete resource with an undefined "id"');
        }

        this.deleting = true;

        const {locale} = this.observableOptions;

        const requestOptions = options;
        if (locale) {
            requestOptions.locale = locale.get();
        }

        return ResourceRequester.delete(this.resourceKey, {...requestOptions, id: this.data.id})
            .then(action((response) => {
                this.id = undefined;
                this.setMultiple(response);
                this.deleting = false;
                this.dirty = false;

                this.destroy();
            }))
            .catch(action((error) => {
                this.deleting = false;
                throw error;
            }));
    }

    @action move = (parentId: string | number) => {
        if (!this.id) {
            throw new Error('Moving does not work for new objects!');
        }

        this.moving = true;

        const {locale} = this.observableOptions;

        const queryOptions = {
            action: 'move',
            destination: parentId,
            locale: locale ? locale.get() : undefined,
        };

        return ResourceRequester.post(this.resourceKey, undefined, {...queryOptions, id: this.id})
            .then(action(() => {
                this.moving = false;
            }))
            .catch(action((error) => {
                this.moving = false;
                throw error;
            }));
    };

    copyFromLocale(locale: string, options: Object = {}) {
        if (!this.id) {
            throw new Error('Copying from another locale does not work for new objects!');
        }

        if (!this.locale) {
            throw new Error('Copying from another locale does only work for objects with locales!');
        }

        return ResourceRequester
            .post(
                this.resourceKey,
                {},
                {...options, action: 'copy-locale', dest: this.locale.get(), id: this.id, locale}
            ).then(action((response) => {
                this.setMultiple(response);
                return response;
            }));
    }

    @action set(path: string, value: mixed) {
        if (path === 'id' && (typeof value === 'string' || typeof value === 'number')) {
            this.id = value;
        }

        jsonpointer.set(this.data, '/' + path, value);
    }

    @action setMultiple(data: Object) {
        if (data.id) {
            this.id = data.id;
        }

        Object.keys(data).forEach((path) => {
            this.set(path, data[path]);
        });
        set(this.data, this.data);

        log.info(
            'ResourceStore changed "' + this.resourceKey + '" data with the ID "' + (this.id || 'undefined') + '"',
            this.data
        );
    }

    @action change(path: string, value: mixed) {
        this.set(path, value);
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

        clonedResourceStore.loading = this.loading;

        when(
            () => !this.loading,
            (): void => {
                clonedResourceStore.data = toJS(this.data);
                clonedResourceStore.loading = false;
            }
        );

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
