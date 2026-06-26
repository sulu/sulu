// @flow
import React from 'react';
import {FormInspector, ResourceFormStore, SingleSelection, ResourceLocator} from 'sulu-admin-bundle/containers';
import {
    fieldTypeDefaultProps,
    findElementByType,
    renderWithRef,
    waitForReaction,
} from 'sulu-admin-bundle/utils/TestHelper';
import {ResourceStore, SingleSelectionStore} from 'sulu-admin-bundle/stores';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import PageTreeRoute from '../../fields/PageTreeRoute';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'de',
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
    this.clearSelection = jest.fn();
    this.select = jest.fn();
    this.destroy = jest.fn();
}));

jest.mock(
    'sulu-admin-bundle/stores/ResourceStore',
    () => jest.fn(function(resourceKey, id, options) {
        this.resourceKey = resourceKey;
        this.id = id;

        if (options) {
            this.locale = options.locale;
        }
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn(function(data, schema) {
    this.data = data;
    this.schema = schema;
    this.change = jest.fn().mockImplementation((name, value) => {
        this.data[name] = value;
    });
    this.validate = jest.fn().mockReturnValue(true);
    this.destroy = jest.fn();
}));

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey) {
        this.resourceKey = resourceStore.resourceKey;
        this.id = resourceStore.id;
        this.locale = resourceStore.locale;

        if (formKey) {
            this.formKey = formKey;
        }
    })
);

jest.mock(
    'sulu-admin-bundle/containers/Form/FormInspector',
    () => jest.fn(function(resourceFormStore) {
        this.id = resourceFormStore.id;
        this.locale = resourceFormStore.locale;
        this.isFieldModified = jest.fn();
        this.options = {
            webspace: 'webspace',
        };
        this.getPathsByTag = jest.fn().mockReturnValue([]);
    })
);

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = {id, url: '/test/' + id};
    });
    this.clear = jest.fn();

    mockExtendObservable(this, {
        item: undefined,
        loading: false,
    });
}));

test('Render a PageTreeRoute', () => {
    const modePromiseValue = 'leaf';
    const modePromise = Promise.resolve(modePromiseValue);
    const modeResolver = jest.fn().mockImplementation(() => modePromise);

    const fieldTypeOptions = {
        modeResolver,
    };

    const value = {
        page: {
            uuid: 'uuid-uuid-uuid-uuid',
        },
        suffix: '/hello',
    };

    const locale = observable.box('de');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 'diuu-diuu-diuu-diuu', {locale}),
            'test'
        )
    );

    const {container, instance: pageTreeRoute} = renderWithRef(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    return modePromise.then(() => {
        return waitForReaction().then(() => {
            expect(container).toMatchSnapshot();
            expect(findElementByType(pageTreeRoute.render(), SingleSelection).props.value).toBe(value.page.uuid);
            expect(SingleSelectionStore).toHaveBeenCalledWith('pages', 'uuid-uuid-uuid-uuid', locale, undefined);
            expect(findElementByType(pageTreeRoute.render(), ResourceLocator).props.value).toBe(value.suffix);
        });
    });
});

test('Render a PageTreeRoute without value', () => {
    const modePromiseValue = 'leaf';
    const modePromise = Promise.resolve(modePromiseValue);
    const modeResolver = jest.fn().mockImplementation(() => modePromise);

    const fieldTypeOptions = {
        modeResolver,
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('pages'), 'test'));

    const {container, instance: pageTreeRoute} = renderWithRef(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    return modePromise.then(() => {
        return waitForReaction().then(() => {
            expect(container).toMatchSnapshot();
            expect(findElementByType(pageTreeRoute.render(), SingleSelection).props.value).toBe(null);
            expect(findElementByType(pageTreeRoute.render(), ResourceLocator).props.value).toBe(null);
        });
    });
});
