// @flow
import log from 'loglevel';
import localizationStore from '../localizationStore';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Load localizations', () => {
    const localizations = [
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
    ];

    localizationStore.setLocalizations(localizations);

    return localizationStore.loadLocalizations().then((localizations) => {
        expect(log.warn).toBeCalled();
        expect(localizations).toBe(localizations);
    });
});
