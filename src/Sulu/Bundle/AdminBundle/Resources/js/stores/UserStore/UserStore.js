// @flow

class UserStore {
    persistentSettings: {[string]: *} = {};

    clear() {
        this.persistentSettings = {};
    }

    setPersistentSetting(key: string, value: *) {
        this.persistentSettings[key] = value;
    }

    getPersistentSetting(key: string) {
        return this.persistentSettings[key];
    }
}

export default new UserStore();
