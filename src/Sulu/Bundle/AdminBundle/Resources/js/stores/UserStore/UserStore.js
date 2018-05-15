// @flow
import {action, observable} from 'mobx';
import moment from 'moment';
import Requester from '../../services/Requester';
import initializer from '../../services/Initializer';
import type {Contact, User} from './types';

class UserStore {
    persistentSettings: {[string]: *} = {};

    @observable loggedIn: boolean = false;
    @observable loading: boolean = false;
    @observable user: ?User = undefined;
    @observable contact: ?Contact = undefined;
    @observable loginError: boolean = false;
    @observable resetSuccess: boolean = false;

    @action clear(stillLoading: boolean = false) {
        this.persistentSettings = {};
        this.loggedIn = false;
        this.loading = stillLoading;
        this.user = undefined;
        this.contact = undefined;
        this.loginError = false;
        this.resetSuccess = false;
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
        moment.locale(user.locale);
    }

    @action setContact(contact: Contact) {
        this.contact = contact;
    }

    @action clearError = () => {
        this.loginError = false;
        this.resetSuccess = false;
    };

    login = (user: string, password: string) => {
        this.setLoading(true);

        return Requester.post('/admin/v2/login', {username: user, password: password})
            .then(() => {
                if (this.user) {
                    // when the user was logged in already and comes again with the same user
                    // we don't need to initialize again
                    if (user === this.user.username) {
                        this.setLoggedIn(true);
                        this.setLoading(false);

                        return;
                    }

                    // the user has now logged in with a different user
                    // we need to clear the data from the old user
                    this.clear(true);
                }

                return initializer.initialize().then(() => {
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
            return Requester.post('/admin/security/reset/email/resend', {user: user})
                .catch(() => {})
                // Bug in flow: https://github.com/facebook/flow/issues/5810
                // $FlowFixMe:
                .finally(() => {
                    this.setLoading(false);
                });
        }

        return Requester.post('/admin/security/reset/email', {user: user})
            .catch(() => {})
            // Bug in flow: https://github.com/facebook/flow/issues/5810
            // $FlowFixMe:
            .finally(() => {
                this.setLoading(false);
                this.setResetSuccess(true);
            });
    }

    logout() {
        return Requester.get('/admin/logout').then(() => {
            this.setLoggedIn(false);
        });
    }

    setPersistentSetting(key: string, value: *) {
        this.persistentSettings[key] = value;
    }

    getPersistentSetting(key: string) {
        return this.persistentSettings[key];
    }
}

export default new UserStore();
