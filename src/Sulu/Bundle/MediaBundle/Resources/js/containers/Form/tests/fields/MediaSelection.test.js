// @flow
import React from 'react';
import log from 'loglevel';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Router from 'sulu-admin-bundle/services/Router';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MediaSelection from '../../fields/MediaSelection';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

jest.mock('../../../MultiMediaSelection', () => jest.fn(() => null));

const MultiMediaSelectionMock: any = jest.requireMock('../../../MultiMediaSelection');

const createFormInspector = (locale: ?string = 'en') => new FormInspector(
    new ResourceFormStore(
        new ResourceStore('test', undefined, {locale: locale ? observable.box(locale) : undefined}),
        'test'
    )
);

const createProps = (overrides = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: createFormInspector(),
    ...overrides,
});

const renderMediaSelection = (overrides = {}) => (
    render(<MediaSelection {...(createProps(overrides): any)} />)
);

const expectThrowSilently = (renderCallback, errorPattern) => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderCallback).toThrow(errorPattern);
    consoleErrorSpy.mockRestore();
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to MultiMediaSelection component', () => {
    renderMediaSelection({
        disabled: true,
        value: {displayOption: undefined, ids: [55, 66, 77]},
    });

    expect(getLatestMockProps(MultiMediaSelectionMock).displayOptions).toEqual([]);
    expect(getLatestMockProps(MultiMediaSelectionMock).disabled).toEqual(true);
    expect(getLatestMockProps(MultiMediaSelectionMock).sortable).toEqual(true);
    expect(getLatestMockProps(MultiMediaSelectionMock).locale.get()).toEqual('en');
    expect(getLatestMockProps(MultiMediaSelectionMock).value).toEqual({ids: [55, 66, 77]});
});

test('Pass content-locale of user to MultiMediaSelection if locale is not present in form-inspector', () => {
    renderMediaSelection({
        formInspector: createFormInspector(null),
        value: {displayOption: undefined, ids: [55, 66, 77]},
    });

    expect(getLatestMockProps(MultiMediaSelectionMock).locale.get()).toEqual('userContentLocale');
});

test('Set default display option if no value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        defaultDisplayOption: {
            name: 'defaultDisplayOption',
            value: 'left',
        },
        displayOptions: {
            name: 'displayOptions',
            value: [{name: 'left', value: true}],
        },
    };

    renderMediaSelection({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith({displayOption: 'left', ids: []}, {'isDefaultValue': true});
});

test('Pass correct props for given schema-options to MultiMediaSelection component', () => {
    const schemaOptions = {
        types: {
            name: 'types',
            value: 'image,video',
        },
        sortable: {
            name: 'sortable',
            value: false,
        },
    };

    renderMediaSelection({schemaOptions});

    expect(getLatestMockProps(MultiMediaSelectionMock).types).toEqual(['image', 'video']);
    expect(getLatestMockProps(MultiMediaSelectionMock).sortable).toEqual(false);
});

test('Do not set default display option if value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        defaultDisplayOption: {
            name: 'defaultDisplayOption',
            value: 'left',
        },
        displayOptions: {
            name: 'displayOptions',
            value: [{name: 'left', value: true}],
        },
    };

    renderMediaSelection({
        onChange: changeSpy,
        schemaOptions,
        value: {displayOption: undefined, ids: []},
    });

    expect(changeSpy).not.toBeCalled();
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMediaSelection({
        disabled: true,
        onChange: changeSpy,
        onFinish: finishSpy,
        value: {displayOption: undefined, ids: [55, 66, 77]},
    });

    getLatestMockProps(MultiMediaSelectionMock).onChange({ids: [33, 44]});

    expect(changeSpy).toBeCalledWith({ids: [33, 44]});
    expect(finishSpy).toBeCalled();
});

test('Should navigate to media if a media is clicked', () => {
    const router = new Router();

    renderMediaSelection({
        disabled: true,
        router,
        value: {displayOption: undefined, ids: [55, 66]},
    });

    getLatestMockProps(MultiMediaSelectionMock).onItemClick(55, {id: 55, locale: 'en', mimeType: 'application/pdf'});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 55, locale: 'en'});

    getLatestMockProps(MultiMediaSelectionMock).onItemClick(66, {id: 66, locale: 'en', mimeType: 'application/pdf'});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 66, locale: 'en'});
});

test('Should throw an error if given value does not have an ids property', () => {
    expectThrowSilently(
        () => renderMediaSelection({value: ({unrelatedProperty: 123}: any)}),
        /"ids" property/
    );
});

test('Should log warning and use ids of objects if given value is an array of objects', () => {
    renderMediaSelection({
        disabled: true,
        value: ([{id: 55}, {id: 66}, {id: 77}]: any),
    });

    expect(getLatestMockProps(MultiMediaSelectionMock).value).toEqual({ids: [55, 66, 77]});
    expect(log.warn).toBeCalledWith(expect.stringContaining('expects an object with an "ids" property as value'));
});

test('Should throw an error if given value is not an object', () => {
    expectThrowSilently(
        () => renderMediaSelection({value: (true: any)}),
        /expects an object/
    );
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    expectThrowSilently(
        () => renderMediaSelection({schemaOptions: {displayOptions: {name: 'displayOptions', value: true}}}),
        /"displayOptions"/
    );
});

test('Should throw an error if displayOptions schemaOption contains an invalid value', () => {
    expectThrowSilently(
        () => renderMediaSelection({
            schemaOptions: {displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}},
        }),
        /"test"/
    );
});

test('Should throw an error if types schemaOption is given but not a string', () => {
    expectThrowSilently(
        () => renderMediaSelection({schemaOptions: {types: {name: 'types', value: true}}}),
        /"types"/
    );
});
