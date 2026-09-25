// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
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

test('Render a PageTreeRoute', async() => {
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

    const {container} = render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    await modePromise;
    await waitFor(() => expect(SingleSelectionStore)
        .toHaveBeenCalledWith('pages', 'uuid-uuid-uuid-uuid', locale, undefined));

    expect(container).toMatchSnapshot();
    expect(screen.getByText('sulu_page.no_page_selected')).toBeInTheDocument();
    expect(screen.getByDisplayValue('hello')).toBeInTheDocument();
});

test('Render a PageTreeRoute without value', async() => {
    const modePromiseValue = 'leaf';
    const modePromise = Promise.resolve(modePromiseValue);
    const modeResolver = jest.fn().mockImplementation(() => modePromise);

    const fieldTypeOptions = {
        modeResolver,
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('pages'), 'test'));

    const {container} = render(
        <PageTreeRoute
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(modeResolver).toHaveBeenCalled();

    await modePromise;
    await waitFor(() => expect(screen.getByRole('textbox')).toHaveValue(''));

    expect(container).toMatchSnapshot();
    expect(screen.getByText('sulu_page.no_page_selected')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveValue('');
});
