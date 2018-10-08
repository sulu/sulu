// @flow
import {ResourceRequester} from '../../../services';
import localizationStore from '../LocalizationStore';

jest.mock('../../../services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

test('Load localizations', () => {
    const response = {
        _embedded: {
            localizations: [
                {
                    name: 'sulu',
                    key: 'sulu',
                },
                {
                    name: 'Sulu Blog',
                    key: 'sulu_blog',
                },
            ],
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);

    const localizationPromise = localizationStore.loadLocalizations();

    return localizationPromise.then((localizations) => {
        // check if promise has been cached
        expect(localizationStore.localizationPromise).toEqual(localizationPromise);
        expect(localizations).toBe(response._embedded.localizations);
    });
});
