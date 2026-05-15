// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {observable} from 'mobx';
import SingleMediaSelectionComponent from '../../../SingleMediaSelection';
import SingleMediaSelection from '../../fields/SingleMediaSelection';

jest.mock('../../../SingleMediaSelection', () => jest.fn(() => null));

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

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

function getLatestSingleMediaSelectionProps() {
    const calls = ((SingleMediaSelectionComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

function expectRenderToThrow(renderFn: () => void, expectedMessage: RegExp) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(renderFn).toThrow(expectedMessage);

    consoleErrorSpy.mockRestore();
}

test('Pass correct props to SingleMediaSelection component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            error={{keyword: 'mandatory', parameters: {}}}
            formInspector={formInspector}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(getLatestSingleMediaSelectionProps().disabled).toEqual(true);
    expect(getLatestSingleMediaSelectionProps().valid).toEqual(false);
    expect(getLatestSingleMediaSelectionProps().locale.get()).toEqual('en');
    expect(getLatestSingleMediaSelectionProps().value).toEqual({id: 33});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: undefined}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(getLatestSingleMediaSelectionProps().locale.get()).toEqual('userContentLocale');
});

test('Set types on SingleMediaSelectionComponent', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        types: {name: 'types', value: 'image,video'},
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(getLatestSingleMediaSelectionProps().types).toEqual(['image', 'video']);
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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value={{displayOption: 'left', id: undefined}}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{displayOption: undefined, id: 55}}
        />
    );

    getLatestSingleMediaSelectionProps().onChange({id: 44});

    expect(changeSpy).toBeCalledWith({id: 44});
    expect(finishSpy).toBeCalled();
});

test('Should call onItemClick if item is clicked', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const router = new Router();

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            router={router}
            value={{displayOption: undefined, id: 55}}
        />
    );

    getLatestSingleMediaSelectionProps().onItemClick(6, {
        id: 6,
        locale: 'de',
        mimeType: 'image/jpeg',
        title: 'Test',
    });

    expect(router.navigate).toBeCalledWith('sulu_media.form', {id: 6, locale: 'de'});
});

test('Should throw an error if given value is not an object', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expectRenderToThrow(
        () => render(
            <SingleMediaSelection
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                value={(55: any)}
            />
        ),
        /expects an object with an "id" property/
    );
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expectRenderToThrow(
        () => render(
            <SingleMediaSelection
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={{displayOptions: {name: 'displayOptions', value: true}}}
            />
        ),
        /"displayOptions"/
    );
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expectRenderToThrow(
        () => render(
            <SingleMediaSelection
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={{displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}}}
            />
        ),
        /"displayOptions"/
    );
});

test('Should throw an error if types schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expectRenderToThrow(
        () => render(
            <SingleMediaSelection
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={{types: {name: 'types', value: true}}}
            />
        ),
        /"types"/
    );
});
