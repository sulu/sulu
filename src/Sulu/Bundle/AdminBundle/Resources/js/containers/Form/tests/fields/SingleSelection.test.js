// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import resourceMetadataStore from '../../../../stores/ResourceMetadataStore';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import SingleSelection from '../../fields/SingleSelection';
import SingleSelectionComponent from '../../../../containers/SingleSelection';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, locale) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = locale;
}));
jest.mock('../../stores/FormStore', () => jest.fn(function(resourceStore) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
}));
jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.locale = formStore.locale;
}));

jest.mock('../../../../stores/ResourceMetadataStore', () => ({
    isSameEndpoint: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass correct props to SingleAutoComplete', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
        value,
    }));
});

test('Pass correct props with schema-options type to SingleAutoComplete', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'auto_complete',
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
        value,
    }));
});

test('Call onChange and onFinish when SingleAutoComplete changes', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    singleSelection.find('SingleAutoComplete').simulate('change', undefined);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});

test('Throw an error if the auto_complete configuration was omitted', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const fieldTypeOptions = {
        default_type: 'auto_complete',
        types: {},
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"auto_complete"/);
});

test('Pass correct props to SingleItemSelection', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'accounts',
        types: {
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        adapter: 'table',
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

test('Pass correct props with schema-options type to SingleItemSelection', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: 'datagrid_overlay',
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        adapter: 'table',
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

test('Throw an error if a none string was passed to schema-options', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const schemaOptions = {
        type: {
            name: 'type',
            value: true,
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                disabled={true}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
                value={value}
            />
        )
    ).toThrow(/"type"/);
});

test('Throw an error if a none string was passed to field-type-options', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = 3;

    const fieldTypeOptions = {
        default_type: true,
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                disabled={true}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                value={value}
            />
        )
    ).toThrow(/"default_type"/);
});

test('Pass correct locale and disabledIds to SingleItemSelection', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(new FormStore(new ResourceStore('accounts', 5, locale)));
    const value = 3;

    resourceMetadataStore.isSameEndpoint.mockReturnValue(true);

    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'accounts',
        types: {
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(resourceMetadataStore.isSameEndpoint).toBeCalledWith('accounts', 'accounts');
    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
        disabledIds: [5],
        locale,
    }));
});

test('Call onChange and onFinish when SingleAutoComplete changes', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'accounts',
        types: {
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    singleSelection.find(SingleSelectionComponent).simulate('change', undefined);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});
