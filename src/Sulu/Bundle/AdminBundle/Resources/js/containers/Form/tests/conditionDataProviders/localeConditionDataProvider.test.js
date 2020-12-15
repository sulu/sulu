// @flow
import {observable} from 'mobx';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceFormStore from '../../stores/ResourceFormStore';
import FormInspector from '../../FormInspector';
import localeConditionDataProvider from '../../conditionDataProviders/localeConditionDataProvider';

jest.mock(
    '../../stores/ResourceFormStore',
    () => jest.fn(function(resourceStore) {
        this.locale = resourceStore.locale;
    })
);

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceStore, id, observableOptions) {
    this.locale = observableOptions?.locale;
}));

test('Return locale from FormInspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {webspace: 'test'})
    );

    expect(localeConditionDataProvider({}, '/test', formInspector)).toEqual({__locale: undefined});
});

test('Return locale from FormInspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 5, {locale: observable.box('en')}), 'test', {webspace: 'test'})
    );

    expect(localeConditionDataProvider({}, '/test', formInspector)).toEqual({__locale: 'en'});
});
