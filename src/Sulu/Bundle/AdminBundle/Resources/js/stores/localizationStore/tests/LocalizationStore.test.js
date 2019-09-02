// @flow
import {ResourceRequester} from '../../../services';
import localizationStore from '../localizationStore';

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
                    country: '',
                    default: '1',
                    language: 'en',
                    locale: 'en',
                    localization: 'en',
                    shadow: '',
                    xDefault: '',
                },
                {
                    country: '',
                    default: '0',
                    language: 'de',
                    locale: 'de',
                    localization: 'de',
                    shadow: '',
                    xDefault: '',
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
