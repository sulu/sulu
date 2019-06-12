// @flow
import {Requester} from 'sulu-admin-bundle/services';
import securityContextStore from '../SecurityContextStore';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn(),
}));

test('Load available actions for permissions with given keys', () => {
    Requester.get.mockReturnValue(Promise.resolve({
        'Sulu': {
            'Global': {
                'sulu.snippets': ['view', 'add'],
            },
            'Test': {
                'sulu.test': ['view', 'add', 'edit'],
            },
        },
    }));

    return securityContextStore.loadAvailableActions('sulu.test').then((actions) => {
        expect(actions).toEqual(['view', 'add', 'edit']);
    });
});

test('Load security contexts for entire system', () => {
    const suluSecurityContexts = {
        'Global': {
            'sulu.snippets': ['view', 'add'],
        },
        'Test': {
            'sulu.test': ['view', 'add', 'edit'],
        },
    };

    Requester.get.mockReturnValue(Promise.resolve({
        'Sulu': suluSecurityContexts,
    }));

    return securityContextStore.loadSecurityContextGroups('Sulu').then((securityContexts) => {
        expect(securityContexts).toEqual(suluSecurityContexts);
    });
});
