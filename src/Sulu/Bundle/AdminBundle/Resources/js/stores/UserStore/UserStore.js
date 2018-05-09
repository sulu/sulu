// @flow
import {action, computed, observable} from 'mobx';
import Requester from '../../services/Requester';
import initializer from '../../services/Initializer';

class UserStore {
    persistentSettings: {[string]: *} = {};

    @observable loading: boolean = false;
    @observable user: ?Object = undefined;
    @observable loginError: ?string = undefined;
    @observable resetError: ?string = undefined;
    @observable resetSuccess: ?string = undefined;

    @computed get loggedIn(): boolean {
        return !!this.user;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setLoginError(error: string) {
        this.loginError = error;
    }

    @action setUser(user: Object) {
        this.user = user;
    }

    @action clearUser() {
        this.user = undefined;
    }

    clear() {
        this.persistentSettings = {};
    }

    login = (user: string, password: string) => {
        this.setLoading(true);

        return Requester.post('/admin/v2/login', {username: user, password: password})
            .then(() => {
                return initializer.initialize().then(() => {
                    this.setLoading(false);
                });
            })
            .catch((error) => {
                this.setLoading(false);
                if (error.status !== 401) {
                    return Promise.reject(error);
                }

                this.setLoginError('Invalid credentials');
            });
    };

    @action clearError = () => {
        this.loginError = undefined;
        this.resetError = undefined;
    };

    resetPassword(user: string) {

    }

    logout() {
        return Requester.get('/admin/v2/logout').then(() => {
            this.clearUser();
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
