// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {createRouterMock, fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import SingleMediaSelection from '../../fields/SingleMediaSelection';

let mockSingleSelectionStoreInstances: Array<Object> = [];

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn(function() {
    mockSingleSelectionStoreInstances.push(this);
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

function mockSingleMediaSelectionOverlay(props) {
    if (!props.open) {
        return null;
    }

    return React.createElement('div', {}, props.types.join(','));
}

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(mockSingleMediaSelectionOverlay));

const SingleSelectionStoreMock = (SingleSelectionStore: any);

function getLatestSingleSelectionStore() {
    const store = mockSingleSelectionStoreInstances[mockSingleSelectionStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected a single selection store to be created');
    }

    return store;
}

function mockSingleSelectionStoreOnce(implementation) {
    SingleSelectionStoreMock.mockImplementationOnce(function(...args) {
        implementation.apply(this, args);
        mockSingleSelectionStoreInstances.push(this);
    });
}

beforeEach(() => {
    mockSingleSelectionStoreInstances = [];
});

function getLatestSingleSelectionStoreCall() {
    const call = SingleSelectionStoreMock.mock.calls[SingleSelectionStoreMock.mock.calls.length - 1];

    if (!call) {
        throw new Error('Expected a single selection store call');
    }

    return call;
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

    expect(getLatestSingleSelectionStoreCall()[0]).toEqual('media');
    expect(getLatestSingleSelectionStoreCall()[1]).toEqual(33);
    expect(getLatestSingleSelectionStoreCall()[2].get()).toEqual('en');
    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(screen.getByText('sulu_media.select_media_singular').closest('.singleItemSelection')).toHaveClass('error');
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
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

    expect(getLatestSingleSelectionStoreCall()[2].get()).toEqual('userContentLocale');
});

test('Set types on SingleMediaSelectionComponent', async() => {
    const user = userEvent.setup();
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

    await user.click(screen.getByRole('button', {name: 'su-image'}));

    expect(screen.getByText('image,video')).toBeInTheDocument();
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

    expect(changeSpy).toHaveBeenCalledWith({displayOption: 'left', id: undefined}, {'isDefaultValue': true});
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

    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestSingleSelectionStore().item = {
        id: 44,
        locale: 'en',
        mimeType: 'application/pdf',
        title: 'Media 44',
    };

    expect(changeSpy).toHaveBeenCalledWith({id: 44});
    expect(finishSpy).toHaveBeenCalled();
});

test('Should call onItemClick if item is clicked', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const router = createRouterMock();

    // $FlowFixMe
    mockSingleSelectionStoreOnce(function() {
        this.item = {id: 6, locale: 'de', mimeType: 'image/jpeg', title: 'Test'};
    });

    render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            value={{displayOption: undefined, id: 55}}
        />
    );

    await user.click(screen.getByText('Test'));

    expect(router.navigate).toHaveBeenCalledWith('sulu_media.form', {id: 6, locale: 'de'});
});

test('Should throw an error if given value is not an object', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={(55: any)}
        />
    )).toThrow(/expects an object with an "id" property/);
});

test('Should throw an error if displayOptions schemaOption contains an invalid value', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: true}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if displayOptions schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{displayOptions: {name: 'displayOptions', value: [{name: 'test', value: true}]}}}
        />
    )).toThrow(/"displayOptions"/);
});

test('Should throw an error if types schemaOption is given but not an array', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    expect(() => render(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: true}}}
        />
    )).toThrow(/"types"/);
});
