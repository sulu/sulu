// @flow
import {observable} from 'mobx';
import jexl from 'jexl';
import userConditionDataProvider from '../../conditionDataProviders/userConditionDataProvider';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../stores/userStore', () => ({
}));

test('Return user from userStore', () => {
    userStore.user = undefined;
    expect(userConditionDataProvider()).toEqual({__user: undefined});

    userStore.user = {
        id: 1,
        username: 'admin',
        locale: 'en',
        fullName: 'Adam Ministrator',
        roles: ['ROLE_USER'],
        settings: {},
    };

    expect(userConditionDataProvider()).toEqual({
        __user: {
            id: 1,
            username: 'admin',
            locale: 'en',
            fullName: 'Adam Ministrator',
            roles: ['ROLE_USER'],
            settings: {},
        },
    });
});

test('Roles of user can be used in jexl expression', () => {
    userStore.user = observable({
        id: 1,
        username: 'admin',
        locale: 'en',
        fullName: 'Adam Ministrator',
        roles: ['ROLE_USER', 'ROLE_SULU_DESIGNER'],
        settings: {},
    });

    const conditionData = userConditionDataProvider();
    expect(jexl.evalSync('"ROLE_SULU_DESIGNER" in __user.roles', conditionData)).toBeTruthy();
    expect(jexl.evalSync('"ROLE_SULU_TESTER" in __user.roles', conditionData)).toBeFalsy();
});
