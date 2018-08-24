// @flow
import log from 'loglevel';
import {clearTranslations, setTranslations, translate} from '../Translator';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

beforeEach(() => {
    clearTranslations();
});

test('Translator should translate translations', () => {
    setTranslations({'save': 'Save', 'delete': 'Delete'}, 'en');

    expect(translate('save')).toBe('Save');
    expect(translate('delete')).toBe('Delete');
});

test('Translator should use the IntlMessageFormat for translation', () => {
    setTranslations({
        'apple_count': 'You have {numApples, plural, =0 {no apples} =1 {one apple} other {# apples}}.',
    }, 'en');
    expect(translate('apple_count', {numApples: 0})).toEqual('You have no apples.');
    expect(translate('apple_count', {numApples: 1})).toEqual('You have one apple.');
    expect(translate('apple_count', {numApples: 4})).toEqual('You have 4 apples.');
});

test('Translator should use the english language for translating dates', () => {
    setTranslations({
        'date': '{date, date}',
    }, 'en');

    expect(translate('date', {date: new Date('1995-12-17')})).toEqual('12/17/1995');
});

test('Translator should return key when translating non-existing keys and log a warning', () => {
    expect(translate('not-existing')).toBe('not-existing');
    expect(log.warn).toBeCalled();
});
