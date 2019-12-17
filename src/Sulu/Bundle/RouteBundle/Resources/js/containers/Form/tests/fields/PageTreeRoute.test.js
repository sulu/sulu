// @flow
import React from 'react';
import {mount} from 'enzyme';
import PageTreeRoute from 'sulu-route-bundle/containers/Form/fields/PageTreeRoute';
import {FormInspector, ResourceFormStore, ResourceLocatorHistory, SingleSelection} from 'sulu-admin-bundle/containers';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ResourceLocator from 'sulu-admin-bundle/components/ResourceLocator';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'de',
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
    this.clearSelection = jest.fn();
    this.select = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn(function(data, schema) {
    this.data = data;
    this.schema = schema;
    this.change = jest.fn().mockImplementation((name, value) => {
        this.data[name] = value;
    });
    this.validate = jest.fn().mockReturnValue(true);
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));

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

const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('pages'), 'test'));

const modePromiseValue = 'leaf';
const modePromise = Promise.resolve(modePromiseValue);
const modeResolver = jest.fn().mockImplementation(() => modePromise);

const fieldTypeOptions = {
    historyResourceKey: 'routes',
    modeResolver: modeResolver,
    options: {history: true},
};

const value = {
    page: {
        uuid: 'uuid-uuid-uuid-uuid',
    },
    suffix: '/hello',
};

test('Render a PageTreeRoute', () => {
    const locale = observable.box('de');

    const pageTreeRoute = mount(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={{
                ...formInspector,
                locale,
                id: 'diuu-diuu-diuu-diuu',
            }}
            value={value}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    return modePromise.then(() => {
        pageTreeRoute.update();
        expect(pageTreeRoute.render()).toMatchSnapshot();
        expect(pageTreeRoute.find(SingleSelection).prop('value')).toBe(value.page.uuid);
        expect(SingleSelectionStore).toHaveBeenCalledWith('pages', 'uuid-uuid-uuid-uuid', locale, undefined);

        const singleSelection = pageTreeRoute.find(SingleSelection);

        singleSelection.instance().singleSelectionStore.item = {};
        singleSelection.update();

        expect(singleSelection.find('.item').text()).toBe('/test/uuid-uuid-uuid-uuid');
        expect(singleSelection.render()).toMatchSnapshot();

        expect(pageTreeRoute.find(ResourceLocator).prop('value')).toBe(value.suffix);
        expect(pageTreeRoute.find(ResourceLocator).prop('mode')).toBe(modePromiseValue);
        expect(pageTreeRoute.find(ResourceLocatorHistory).prop('id')).toBe('diuu-diuu-diuu-diuu');
    });
});

test('Render a PageTreeRoute without history', () => {
    const pageTreeRoute = mount(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                ...fieldTypeOptions,
                history: false,
            }}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    return modePromise.then(() => {
        pageTreeRoute.update();
        expect(pageTreeRoute.render()).toMatchSnapshot();
        expect(pageTreeRoute.find(ResourceLocatorHistory).length).toBe(0);
    });
});

test('Render a PageTreeRoute without value', () => {
    const pageTreeRoute = mount(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    return modePromise.then(() => {
        pageTreeRoute.update();
        expect(pageTreeRoute.render()).toMatchSnapshot();
        expect(pageTreeRoute.find(SingleSelection).prop('value')).toBe(null);
        expect(pageTreeRoute.find(ResourceLocator).prop('value')).toBe(null);
    });
});
