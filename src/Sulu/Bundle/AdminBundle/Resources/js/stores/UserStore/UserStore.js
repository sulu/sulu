// @flow
import 'core-js/library/fn/promise';
import {action, computed, observable} from 'mobx';
import debounce from 'debounce';
import {Config, Requester} from '../../services';
import initializer from '../../services/Initializer';
import localizationStore from '../LocalizationStore';
import type {Contact, User} from './types';

const UPDATE_PERSISTENT_SETTINGS_DELAY = 2500;

class UserStore {
    @observable persistentSettings: Map<string, string> = new Map();
    dirtyPersistentSettings: Array<string> = [];

    @observable user: ?User = undefined;
    @observable contact: ?Contact = undefined;
    @observable contentLocale: string = Config.fallbackLocale;

    @observable loggedIn: boolean = false;
    @observable loading: boolean = false;
    @observable loginError: boolean = false;
    @observable resetSuccess: boolean = false;

    @action clear() {
        this.persistentSettings = new Map();
        this.loggedIn = false;
        this.loading = false;
        this.user = undefined;
        this.contact = undefined;
        this.loginError = false;
        this.resetSuccess = false;
    }

    @computed get systemLocale() {
        return this.user ? this.user.locale : Config.fallbackLocale;
    }

    @action setLoggedIn(loggedIn: boolean) {
        this.loggedIn = loggedIn;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setLoginError(loginError: boolean) {
        this.loginError = loginError;
    }

    @action setResetSuccess(resetSuccess: boolean) {
        this.resetSuccess = resetSuccess;
    }

    @action setUser(user: User) {
        this.user = user;

        const persistentSettings = this.user.settings;
        Object.keys(persistentSettings).forEach((key) => {
            this.persistentSettings.set(key, persistentSettings[key]);
        });

        // TODO this code should be adjusted/removed when a proper content-locale handling is implemented
        // load and use first (default) localization of first webspace as content-locale for the user
        localizationStore.loadLocalizations().then(action((localizations) => {
            const defaultLocalizations = localizations.filter((localization) => localization.default);
            const fallbackLocalization = defaultLocalizations.length ? defaultLocalizations[0] : localizations[0];
            this.contentLocale = fallbackLocalization ? fallbackLocalization.locale : this.contentLocale;
        }));
    }

    @action setContact(contact: Contact) {
        this.contact = contact;
    }

    @action setFullName(fullName: string){
        if (this.contact){
            this.contact.fullName = fullName;
        }
    }

    login = (user: string, password: string) => {
        this.setLoading(true);

        return Requester.post(Config.endpoints.loginCheck, {username: user, password: password})
            .then(() => {
                if (this.user) {
                    // when the user was logged in already and comes again with the same user
                    // we don't need to initialize again
                    if (user === this.user.username) {
                        this.setLoggedIn(true);
                        this.setLoading(false);

                        return;
                    }

                    this.clear();
                }

                this.setLoading(true);
                return initializer.initialize(true).then(() => {
                    this.setLoading(false);
                });
            })
            .catch((error) => {
                this.setLoading(false);
                if (error.status !== 401) {
                    return Promise.reject(error);
                }

                this.setLoginError(true);
            });
    };

    resetPassword(user: string) {
        this.setLoading(true);

        if (this.resetSuccess) {
            // if email was already sent use different api
            return Requester.post(Config.endpoints.resetResend, {user: user})
                .then(() => {
                    this.setLoading(false);
                })
                .catch((error) => {
                    if (error.status !== 400) {
                        return Promise.reject(error);
                    }
                    this.setLoading(false);
                });
        }

        return Requester.post(Config.endpoints.reset, {user: user})
            .then(() => {
                this.setLoading(false);
                this.setResetSuccess(true);
            })
            .catch((error) => {
                this.setLoading(false);
                this.setResetSuccess(true);
                if (error.status !== 400) {
                    return Promise.reject(error);
                }
            });
    }

    logout() {
        return Requester.get(Config.endpoints.logout).then(() => {
            this.setLoggedIn(false);
        });
    }

    updatePersistentSettings = debounce(() => {
        const persistentSettings = this.dirtyPersistentSettings.reduce((persistentSettings, persistentSettingKey) => {
            if (this.persistentSettings.has(persistentSettingKey)) {
                persistentSettings[persistentSettingKey] = this.persistentSettings.get(persistentSettingKey);
            }
            return persistentSettings;
        }, {});

        Requester.patch(Config.endpoints.profileSettings, persistentSettings);

        this.dirtyPersistentSettings.splice(0, this.dirtyPersistentSettings.length);
    }, UPDATE_PERSISTENT_SETTINGS_DELAY);

    @action setPersistentSetting(key: string, value: *) {
        if (this.persistentSettings.get(key) === value) {
            return;
        }

        this.persistentSettings.set(key, value);
        this.dirtyPersistentSettings.push(key);
        this.updatePersistentSettings();
    }

    getPersistentSetting(key: string): * {
        const value = this.persistentSettings.get(key);

        return value;
    }
}

export default new UserStore();
