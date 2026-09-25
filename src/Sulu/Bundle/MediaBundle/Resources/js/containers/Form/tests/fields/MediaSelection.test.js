// @flow
import React from 'react';
import log from 'loglevel';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {createRouterMock, fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import MultiSelectionStore from 'sulu-admin-bundle/stores/MultiSelectionStore';
import MediaSelection from '../../fields/MediaSelection';

let mockMultiSelectionStoreInstances: Array<Object> = [];

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

jest.mock('sulu-admin-bundle/stores/MultiSelectionStore', () => jest.fn(function() {
    this.loadItems = jest.fn();

    mockExtendObservable(this, {
        items: [],
    });
    mockMultiSelectionStoreInstances.push(this);
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
    this.destroy = jest.fn();
    this.clear = jest.fn();
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

const ListStoreMock = (ListStore: any);
const MultiSelectionStoreMock = (MultiSelectionStore: any);

function getLatestMultiSelectionStore() {
    const store = mockMultiSelectionStoreInstances[mockMultiSelectionStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected a multi selection store to be created');
    }

    return store;
}

function mockMultiSelectionStoreOnce(implementation) {
    MultiSelectionStoreMock.mockImplementationOnce(function(...args) {
        implementation.apply(this, args);
        mockMultiSelectionStoreInstances.push(this);
    });
}

beforeEach(() => {
    mockMultiSelectionStoreInstances = [];
});

function getLatestMultiSelectionStoreCall() {
    const call = MultiSelectionStoreMock.mock.calls[MultiSelectionStoreMock.mock.calls.length - 1];

    if (!call) {
        throw new Error('Expected a multi selection store call');
    }

    return call;
}

function getLatestMediaListStoreCall() {
    const calls = ListStoreMock.mock.calls.filter((call) => call[0] === 'media');
    const call = calls[calls.length - 1];

    if (!call) {
        throw new Error('Expected a media list store call');
    }

    return call;
}

test('Pass correct props to MultiMediaSelection component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{displayOption: undefined, ids: [55, 66, 77]}}
        />
    );

    expect(getLatestMultiSelectionStoreCall()[0]).toEqual('media');
    expect(getLatestMultiSelectionStoreCall()[1]).toEqual([55, 66, 77]);
    expect(getLatestMultiSelectionStoreCall()[2].get()).toEqual('en');
    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(screen.queryByRole('button', {name: 'su-display-default su-angle-down'})).not.toBeInTheDocument();
});

test('Pass content-locale of user to MultiMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={{displayOption: undefined, ids: [55, 66, 77]}}
        />
    );

    expect(getLatestMultiSelectionStoreCall()[2].get()).toEqual('userContentLocale');
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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith({displayOption: 'left', ids: []}, {'isDefaultValue': true});
});

test('Pass correct props for given schema-options to MultiMediaSelection component', () => {
    const changeSpy = jest.fn();
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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    // $FlowFixMe
    mockMultiSelectionStoreOnce(function() {
        this.loadItems = jest.fn();
        this.items = [
            {
                id: 1,
                mimeType: 'image/jpeg',
                title: 'Media 1',
                thumbnails: {},
            },
        ];
    });

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(getLatestMediaListStoreCall()[4].types).toEqual('image,video');
    expect(screen.queryByLabelText('su-more')).not.toBeInTheDocument();
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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value={{displayOption: undefined, ids: []}}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
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
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{displayOption: undefined, ids: [55, 66, 77]}}
        />
    );

    getLatestMultiSelectionStore().items.push(
        {id: 33, mimeType: 'application/pdf', title: 'Media 33', thumbnails: {}},
        {id: 44, mimeType: 'application/pdf', title: 'Media 44', thumbnails: {}}
    );

    expect(changeSpy).toHaveBeenCalledWith({ids: [33, 44]});
    expect(finishSpy).toHaveBeenCalled();
});

test('Should navigate to media if a media is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const router = createRouterMock();

    // $FlowFixMe
    mockMultiSelectionStoreOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        mockExtendObservable(this, {
            items: selectedIds.map((id) => ({
                id,
                locale: 'en',
                mimeType: 'application/pdf',
                title: `Media ${id}`,
                thumbnails: {},
            })),
        });
    });

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            router={router}
            value={{displayOption: undefined, ids: [55, 66]}}
        />
    );

    await user.click(screen.getByText('Media 55'));
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 55, locale: 'en'});
    await user.click(screen.getByText('Media 66'));
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 66, locale: 'en'});
});

test('Should throw an error if given value does not have an ids property', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={({unrelatedProperty: 123}: any)}
        />
    )).toThrow(/"ids" property/);
});

test('Should log warning and use ids of objects if given value is an array of objects', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={([{id: 55}, {id: 66}, {id: 77}]: any)}
        />
    );

    expect(getLatestMultiSelectionStoreCall()[1]).toEqual([55, 66, 77]);
    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('expects an object with an "ids" property as value'));
});

test('Should throw an error if displayOptions schemaOption value is not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: true}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if given value is not an object', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={(true: any)}
        />
    )).toThrow(/expects an object/);
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: true}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if displayOptions schemaOption is given but contains an invalid value', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}}}
        />
    )).toThrow(/"test"/);
});

test('Should throw an error if types schemaOption value is not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: true}}}
        />
    )).toThrow(/"types"/);
});

test('Should throw an error if types schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: true}}}
        />
    )).toThrow(/"types"/);
});
