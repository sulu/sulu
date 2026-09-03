// @flow
import resolveItemView from '../resolveItemView';

test('Return the view name unchanged when no placeholder map is given', () => {
    const result = resolveItemView(
        'sulu_contact.account_edit_form',
        {id: 'id'},
        undefined,
        {id: 6}
    );

    expect(result).toEqual({parameters: {id: 6}, view: 'sulu_contact.account_edit_form'});
});

test('Replace placeholders in the view name using the given item', () => {
    const result = resolveItemView(
        'sulu_article.article.edit_tabs_{group}',
        {id: 'id', locale: 'locale'},
        {templateGroup: 'group'},
        {id: 6, locale: 'en', templateGroup: 'news'}
    );

    expect(result).toEqual({
        parameters: {id: 6, locale: 'en'},
        view: 'sulu_article.article.edit_tabs_news',
    });
});

test('Read nested values from the item using a json-pointer path', () => {
    const result = resolveItemView(
        'sulu_page.page_edit_form',
        {'properties/locale': 'locale', id: 'uuid'},
        undefined,
        {id: 2, properties: {locale: 'de'}}
    );

    expect(result).toEqual({parameters: {locale: 'de', uuid: 2}, view: 'sulu_page.page_edit_form'});
});
