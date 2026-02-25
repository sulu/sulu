// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {observable} from 'mobx';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SingleMediaSelection from '../../fields/SingleMediaSelection';

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
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

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));
jest.mock('../../../SingleMediaSelection', () => jest.fn(() => null));

const SingleMediaSelectionComponentMock: any = jest.requireMock('../../../SingleMediaSelection');

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

const renderSingleMediaSelection = (overrides = {}) => (
    render(<SingleMediaSelection {...(createProps(overrides): any)} />)
);

const expectThrowSilently = (renderCallback, errorPattern) => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderCallback).toThrow(errorPattern);
    consoleErrorSpy.mockRestore();
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to SingleMediaSelection component', () => {
    renderSingleMediaSelection({
        disabled: true,
        error: {keyword: 'mandatory', parameters: {}},
        value: {displayOption: undefined, id: 33},
    });

    expect(getLatestMockProps(SingleMediaSelectionComponentMock).disabled).toEqual(true);
    expect(getLatestMockProps(SingleMediaSelectionComponentMock).valid).toEqual(false);
    expect(getLatestMockProps(SingleMediaSelectionComponentMock).locale.get()).toEqual('en');
    expect(getLatestMockProps(SingleMediaSelectionComponentMock).value).toEqual({id: 33});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    renderSingleMediaSelection({
        disabled: true,
        formInspector: createFormInspector(null),
        value: {displayOption: undefined, id: 33},
    });

    expect(getLatestMockProps(SingleMediaSelectionComponentMock).locale.get()).toEqual('userContentLocale');
});

test('Set types on SingleMediaSelectionComponent', () => {
    const schemaOptions = {
        types: {name: 'types', value: 'image,video'},
    };

    renderSingleMediaSelection({schemaOptions});

    expect(getLatestMockProps(SingleMediaSelectionComponentMock).types).toEqual(['image', 'video']);
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
            value: [{name: 'left', value: 'true'}],
        },
    };

    renderSingleMediaSelection({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith({displayOption: 'left', id: undefined}, {'isDefaultValue': true});
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
            value: [{name: 'left', value: 'true'}],
        },
    };

    renderSingleMediaSelection({
        onChange: changeSpy,
        schemaOptions,
        value: {displayOption: 'left', id: undefined},
    });

    expect(changeSpy).not.toBeCalled();
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderSingleMediaSelection({
        disabled: true,
        onChange: changeSpy,
        onFinish: finishSpy,
        value: {displayOption: undefined, id: 55},
    });

    getLatestMockProps(SingleMediaSelectionComponentMock).onChange({id: 44});

    expect(changeSpy).toBeCalledWith({id: 44});
    expect(finishSpy).toBeCalled();
});

test('Should call onItemClick if item is clicked', () => {
    const router = new Router();

    renderSingleMediaSelection({
        disabled: true,
        router,
        value: {displayOption: undefined, id: 55},
    });

    getLatestMockProps(SingleMediaSelectionComponentMock).onItemClick(
        6,
        {id: 6, locale: 'de', title: 'Test', mimeType: 'image/jpeg'}
    );

    expect(router.navigate).toBeCalledWith('sulu_media.form', {id: 6, locale: 'de'});
});

test('Should throw an error if given value is not an object', () => {
    expectThrowSilently(
        () => renderSingleMediaSelection({value: (55: any)}),
        /expects an object with an "id" property/
    );
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    expectThrowSilently(
        () => renderSingleMediaSelection({schemaOptions: {displayOptions: {name: 'displayOptions', value: true}}}),
        /"displayOptions"/
    );
});

test('Should throw an error if displayOptions schemaOption values are invalid', () => {
    expectThrowSilently(
        () => renderSingleMediaSelection({
            schemaOptions: {displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}},
        }),
        /"displayOptions"/
    );
});

test('Should throw an error if types schemaOption is given but not a string', () => {
    expectThrowSilently(
        () => renderSingleMediaSelection({schemaOptions: {types: {name: 'types', value: true}}}),
        /"types"/
    );
});
