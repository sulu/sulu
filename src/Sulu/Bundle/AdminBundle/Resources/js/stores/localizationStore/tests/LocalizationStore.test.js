// @flow
import localizationStore from '../localizationStore';

test('Load localizations', () => {
    const localizations = [
        {
            country: '',
            default: '1',
            language: 'en',
            locale: 'en',
            localization: 'en',
            shadow: '',
        },
        {
            country: '',
            default: '0',
            language: 'de',
            locale: 'de',
            localization: 'de',
            shadow: '',
        },
    ];

    localizationStore.setLocalizations(localizations);

    expect(localizationStore.localizations).toEqual(localizations);
});
