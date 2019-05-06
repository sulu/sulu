// @flow
import {action, observable} from 'mobx';
import moment from 'moment';
import userStore from '../../stores/UserStore';
import Config from '../Config';
import {setTranslations} from '../../utils/Translator';
import Requester from '../Requester';
import {resourceRouteRegistry} from '../ResourceRequester';
import type {UpdateConfigHook} from './types';

function getBrowserLanguage() {
    // detect browser locale (ie, ff, chrome fallbacks)
    const language = window.navigator.languages ? window.navigator.languages[0] : null;

    return language || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;
}

function getDefaultLocale() {
    const browserLanguage = getBrowserLanguage();

    // select only language
    const locale = browserLanguage.slice(0, 2).toLowerCase();
    if (Config.translations.indexOf(locale) === -1) {
        return Config.fallbackLocale;
    }

    return locale;
}

function setMomentLocale() {
    moment.locale(getBrowserLanguage());
}

class Initializer {
    @observable initialized: boolean = false;
    @observable initializedTranslationsLocale: ?string;
    @observable loading: boolean = false;
    updateConfigHooks: {[string]: Array<UpdateConfigHook>} = {};

    @action clear() {
        this.initialized = false;
        this.initializedTranslationsLocale = undefined;
        this.loading = false;
    }

    @action setInitialized() {
        this.initialized = true;
    }

    @action setInitializedTranslationsLocale(locale: string) {
        this.initializedTranslationsLocale = locale;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    addUpdateConfigHook(bundle: string, hook: UpdateConfigHook) {
        if (!this.updateConfigHooks[bundle]) {
            this.updateConfigHooks[bundle] = [];
        }
        this.updateConfigHooks[bundle].push(hook);
    }

    initializeSymfonyRouting() {
        return Requester.get(Config.endpoints.routing).then((data) => {
            resourceRouteRegistry.setRoutingData(data);
        });
    }

    initializeTranslations() {
        const locale = userStore.user ? userStore.user.locale : getDefaultLocale();

        const promise = this.initializedTranslationsLocale === locale
            ? Promise.resolve()
            : Requester.get(Config.endpoints.translations + '?locale=' + locale).then((translations) => {
                setTranslations(translations, locale);
                this.setInitializedTranslationsLocale(locale);
            });

        return promise.then(() => {
            this.setLoading(false);
        });
    }

    initialize() {
        this.setLoading(true);

        const configPromise = Requester.get(Config.endpoints.config);
        const routePromise = this.initializeSymfonyRouting();

        return Promise.all([configPromise, routePromise])
            .then(([config]) => {
                if (!this.initialized) {
                    setMomentLocale();
                }

                for (const bundle in this.updateConfigHooks) {
                    this.updateConfigHooks[bundle].forEach((hook) => {
                        hook(config[bundle], this.initialized);
                    });
                }

                this.setInitialized();
                return this.initializeTranslations();
            })
            .catch((error) => {
                if (error.status !== 401) {
                    return Promise.reject(error);
                }
                return this.initializeTranslations();
            });
    }
}

export default new Initializer();
