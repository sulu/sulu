// @flow
import userStore from '../UserStore';

test('Should clear the user store', () => {
    userStore.setPersistentSetting('something', 'somevalue');
    expect(Object.keys(userStore.persistentSettings)).toHaveLength(1);
    userStore.clear();
    expect(Object.keys(userStore.persistentSettings)).toHaveLength(0);
});

test('Should set persistent setting', () => {
    userStore.setPersistentSetting('categories.sortColumn', 'name');
    expect(userStore.getPersistentSetting('categories.sortColumn')).toEqual('name');
});
