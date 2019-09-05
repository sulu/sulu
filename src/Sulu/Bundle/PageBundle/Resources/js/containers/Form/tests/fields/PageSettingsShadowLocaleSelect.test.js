// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import PageSettingsShadowLocaleSelect from '../../fields/PageSettingsShadowLocaleSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
        this.getValueByPath = jest.fn();
        this.locale = formStore.locale;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, options) {
        this.options = options;
        this.locale = resourceStore.locale;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, options) {
        this.locale = options.locale;
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass correct props to SingleSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['en', 'de', 'nl'];
        }
    });

    const pageSettingsShadowSelect = shallow(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value="de"
        />
    );

    expect(pageSettingsShadowSelect.find('SingleSelect').prop('disabled')).toEqual(true);
    expect(pageSettingsShadowSelect.find('SingleSelect').prop('value')).toEqual('de');
    expect(pageSettingsShadowSelect.find('Option').at(0).prop('children')).toEqual('de');
    expect(pageSettingsShadowSelect.find('Option').at(0).prop('value')).toEqual('de');
    expect(pageSettingsShadowSelect.find('Option').at(1).prop('children')).toEqual('nl');
    expect(pageSettingsShadowSelect.find('Option').at(1).prop('value')).toEqual('nl');
});

test('Pass correct props to SingleSelect when no shadow-locale exists', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('de')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['de'];
        }
    });

    const pageSettingsShadowSelect = shallow(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(pageSettingsShadowSelect.find('SingleSelect').prop('disabled')).toEqual(true);
    expect(pageSettingsShadowSelect.find('SingleSelect').prop('value')).toEqual(undefined);
    expect(pageSettingsShadowSelect.find('Option').length).toEqual(0);
});

test('Call onChange and onFinish if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('nl')}),
            'test'
        )
    );
    formInspector.getValueByPath.mockImplementation((path) => {
        if (path === '/contentLocales') {
            return ['en', 'de', 'nl'];
        }
    });

    const pageSettingsShadowSelect = shallow(
        <PageSettingsShadowLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    pageSettingsShadowSelect.find('SingleSelect').prop('onChange')('en');
    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
