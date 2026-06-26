// @flow
import React from 'react';
import log from 'loglevel';
import {render, waitFor} from '@testing-library/react';
import {observable, extendObservable as mockExtendObservable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import SingleSelectionStore from '../../../../stores/SingleSelectionStore';
import userStore from '../../../../stores/userStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelection from '../../fields/SingleSelection';

let mockSingleAutoCompleteProps: Object = {};
let mockResourceSingleSelectProps: Object = {};
let mockSingleSelectionProps: Object = {};

const mockReact = require('react');

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, locale) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = locale;
}));

jest.mock('../../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, selectedItemId, locale) {
    this.resourceKey = resourceKey;
    this.locale = locale;
    this.set = jest.fn();
    this.loading = false;

    mockExtendObservable(this, {item: selectedItemId ? {id: selectedItemId} : undefined});
}));

jest.mock('../../../../stores/userStore', () => ({}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.options = options;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.getValueByPath = jest.fn();
    this.addFinishFieldHandler = jest.fn();
}));

jest.mock('../../../../utils/Translator');

jest.mock('../../../SingleAutoComplete', () => jest.fn((props) => {
    mockSingleAutoCompleteProps = props;

    return mockReact.createElement('div');
}));

jest.mock('../../../ResourceSingleSelect', () => jest.fn((props) => {
    mockResourceSingleSelectProps = props;

    return mockReact.createElement('div');
}));

jest.mock('../../../SingleSelection', () => jest.fn((props) => {
    mockSingleSelectionProps = props;

    return mockReact.createElement('div');
}));

beforeEach(() => {
    jest.clearAllMocks();
    mockSingleAutoCompleteProps = {};
    mockResourceSingleSelectProps = {};
    mockSingleSelectionProps = {};
    // $FlowFixMe
    userStore.contentLocale = undefined;
});

function expectSingleSelectionToThrow(props, error) {
    expect(() => {
        const singleSelection = new SingleSelection(({
            ...fieldTypeDefaultProps,
            ...props,
        }: any));

        singleSelection.render();
    }).toThrow(error);
}

test('Pass correct props and SingleSelectionStore to SingleAutoComplete container', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, locale),
            'test'
        )
    );

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

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="entity-id"
        />
    );

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        options: {},
        searchProperties: ['name', 'number'],
        selectionStore: expect.anything(),
    }));

    expect(SingleSelectionStore).toHaveBeenCalledWith('accounts', 'entity-id', locale);
});

test('Pass correct options to SingleAutoComplete with deprecated data_path_to_auto_complete schema option', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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
        data_path_to_auto_complete: {
            name: 'data_path_to_auto_complete',
            value: [
                {name: 'id', value: 'accountId'},
            ],
        },
    };

    formInspector.getValueByPath.mockReturnValue(5);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="entitiy-id"
        />
    );

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/id');
    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        options: {
            accountId: 5,
        },
    }));
    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining(
        'The "data_path_to_auto_complete" option is deprecated'
    ));
});

test('Use locale from userStore and pass correct props with schema-options type to SingleAutoComplete', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'en';

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
            list_overlay: {
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

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="entity-id"
        />
    );

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        options: {},
        searchProperties: ['name', 'number'],
        selectionStore: expect.anything(),
    }));

    expect(mockSingleAutoCompleteProps.selectionStore.resourceKey).toEqual('accounts');
    expect(mockSingleAutoCompleteProps.selectionStore.item).toEqual({id: 'entity-id'});
    expect(mockSingleAutoCompleteProps.selectionStore.locale.get()).toEqual('en');
});

test('Call onChange and onFinish when item of auto_complete SingleSelectionStore changes', async() => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

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

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="entity-id"
        />
    );

    mockSingleAutoCompleteProps.selectionStore.item = {id: 'new-entity-id'};

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith('new-entity-id'));
    expect(finishSpy).toHaveBeenCalledWith();
});

// eslint-disable-next-line max-len
test('Handle object without warning when "use_deprecated_object_data_format" option of auto_complete is set', async() => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, undefined),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

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
        use_deprecated_object_data_format: {name: 'use_deprecated_object_data_format', value: true},
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={({id: 'old-entity-id'}: any)}
        />
    );

    expect(mockSingleAutoCompleteProps.selectionStore.item).toEqual({id: 'old-entity-id'});
    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining(
        '"use_deprecated_object_data_format" param is deprecated'
    ));
    expect(log.warn).not.toHaveBeenCalledWith(expect.stringContaining('expects an id as value but received an object'));

    mockSingleAutoCompleteProps.selectionStore.item = {id: 'new-entity-id'};

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith({id: 'new-entity-id'}));
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Throw an error if the auto_complete configuration was omitted', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {},
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
    }, /"auto_complete"/);
});

test('Pass correct props to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'name',
                id_property: 'id',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{editable: {name: 'editable', value: true}}}
            value={value}
        />
    );

    expect(mockResourceSingleSelectProps).toEqual(expect.objectContaining({
        displayProperty: 'name',
        editable: true,
        idProperty: 'id',
        overlayTitle: 'sulu_contact.overlay_title',
    }));
});

test('Call onChange and onFinish when SingleSelect changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'name',
                id_property: 'id',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    mockResourceSingleSelectProps.onChange(2);

    expect(changeSpy).toHaveBeenCalledWith(2);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Throw an error if no display_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {},
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
    }, /"display_property"/);
});

test('Throw an error if no id_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        resource_key: 'accounts',
        types: {
            single_select: {
                display_property: 'something',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
    }, /"id_property"/);
});

test('Pass correct props to SingleItemSelection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                list_key: 'accounts_list',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(mockSingleSelectionProps).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: true,
        listKey: 'accounts_list',
        detailOptions: {},
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: undefined,
        listOptions: {},
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

test('Pass resourceKey as listKey to SingleItemSelection if no listKey is given', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(mockSingleSelectionProps.listKey).toEqual('accounts');
});

test('Pass null as value to SingleSelection for list_overlay', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(mockSingleSelectionProps.value).toEqual(null);
});

test('Should log warning and use id of object if given value is an object instead of an id', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={({id: 125}: any)}
        />
    );

    expect(mockSingleSelectionProps.value).toEqual(125);
    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('expects an id as value but received an object'));
});

test('Throw an error if form_options_to_list_options schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            form_options_to_list_options: {
                name: 'form_options_to_api',
                value: 'test',
            },
        },
        value: 3,
    }, '"form_options_to_list_options"');
});

test('Throw an error if request_parameters schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            request_parameters: {
                name: 'request_parameters',
                value: 'test',
            },
        },
        value: 3,
    }, '"request_parameters"');
});

test('Should throw an error if "resource_store_properties_to_request" schema option is not an array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {},
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            resource_store_properties_to_request: {name: 'resource_store_properties_to_request', value: 'not-an-array'},
        },
    }, '"resource_store_properties_to_request"');
});

test('Throw an error if item_disabled_condition schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            item_disabled_condition: {
                name: 'item_disabled_condition',
                value: [],
            },
        },
        value: 3,
    }, '"item_disabled_condition"');
});

test('Throw an error if allow_deselect_for_disabled_items schema option is not a boolean', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            allow_deselect_for_disabled_items: {
                name: 'allow_deselect_for_disabled_items',
                value: 'not-boolean',
            },
        },
        value: 3,
    }, '"allow_deselect_for_disabled_items"');
});

test('Throw an error if detail_options has wrong value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                detail_options: 'test',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        value: 3,
    }, '"detail_options"');
});

test('Pass correct props with schema-options type to SingleItemSelection', () => {
    const options = {
        segment: 'developer',
        webspace: 'sulu',
    };

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                detail_options: {
                    'ghost-content': true,
                },
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: false,
        },
        form_options_to_list_options: {
            name: 'form_options_to_list_options',
            value: [
                {name: 'segment'},
                {name: 'webspace'},
            ],
        },
        type: {
            name: 'type',
            value: 'list_overlay',
        },
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: 'status == "inactive"',
        },
        types: {
            name: 'types',
            value: 'test',
        },
        request_parameters: {
            name: 'request_parameters',
            value: [
                {
                    name: 'rootKey',
                    value: 'testRootKey',
                },
            ],
        },
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test', options));

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/otherPropertyName');

    expect(mockSingleSelectionProps).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: false,
        detailOptions: {
            'ghost-content': true,
            rootKey: 'testRootKey',
            dynamicKey: 'value-returned-by-form-inspector',
        },
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: 'status == "inactive"',
        listOptions: {
            segment: 'developer',
            webspace: 'sulu',
            types: 'test',
            rootKey: 'testRootKey',
            dynamicKey: 'value-returned-by-form-inspector',
        },
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

// eslint-disable-next-line max-len
test('Should update props of SingleItemSelection when value of "resource_store_properties_to_request" property is changed', async() => {
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    const schemaOptions = {
        resource_store_properties_to_request: {
            name: 'resource_store_properties_to_request',
            value: [
                {
                    name: 'dynamicKey',
                    value: 'otherPropertyName',
                },
            ],
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test', {}));

    const formInspectorValues = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(formInspector.addFinishFieldHandler).toHaveBeenCalled();
    expect(mockSingleSelectionProps.detailOptions).toEqual({
        dynamicKey: 'first-value',
    });
    expect(mockSingleSelectionProps.listOptions).toEqual({
        dynamicKey: 'first-value',
    });

    formInspectorValues['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];
    finishFieldHandler('/otherPropertyName');

    await waitFor(() => expect(mockSingleSelectionProps.detailOptions).toEqual({
        dynamicKey: 'second-value',
    }));
    expect(mockSingleSelectionProps.listOptions).toEqual({
        dynamicKey: 'second-value',
    });
});

test('Throw an error if "type" schema-options is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    expectSingleSelectionToThrow({
        disabled: true,
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            type: {
                name: 'type',
                value: true,
            },
        },
        value: 3,
    }, /"type"/);
});

test('Throw an error if "default_type" field-type-option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    expectSingleSelectionToThrow({
        disabled: true,
        fieldTypeOptions,
        formInspector,
        value: 3,
    }, /"default_type"/);
});

test('Pass content locale from user to SingleItemSelection if form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('accounts', 5), 'test'));

    // $FlowFixMe
    userStore.contentLocale = 'en';

    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(mockSingleSelectionProps.locale.get()).toEqual('en');
});

test('Pass correct locale and disabledIds to SingleItemSelection', () => {
    const locale = observable.box('en');
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('accounts', 5, locale), 'test'));
    const value = 3;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(mockSingleSelectionProps).toEqual(expect.objectContaining({
        disabledIds: [5],
        locale,
    }));
});

test('Call onChange and onFinish when SingleSelection changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = 6;

    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    mockSingleSelectionProps.onChange(undefined);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not fail when SingleItemSelection item is clicked without configured view', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            router={router}
            value={6}
        />
    );

    expect(mockSingleSelectionProps.onItemClick).toEqual(undefined);
    expect(router.navigate).not.toHaveBeenCalled();
});

test('Navigate when SingleItemSelection item is clicked with configured view', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'accounts',
        view: {
            name: 'sulu_contact.account_edit_form',
            result_to_view: {
                id: 'id',
            },
        },
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['name'],
                empty_text: 'sulu_contact.nothing',
                icon: 'su-account',
                overlay_title: 'sulu_contact.overlay_title',
            },
        },
    };

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            router={router}
            value={6}
        />
    );

    mockSingleSelectionProps.onItemClick(6, {id: 6});

    expect(router.navigate).toHaveBeenCalledWith('sulu_contact.account_edit_form', {id: 6});
});

test('Should throw an error if "types" schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expectSingleSelectionToThrow({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {types: {name: 'types', value: []}},
    }, /"types"/);
});

test('Should throw an error if no "resource_key" option is passed in fieldOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));

    expectSingleSelectionToThrow({
        fieldTypeOptions: {default_type: 'list_overlay'},
        formInspector,
        schemaOptions: {types: {name: 'types', value: []}},
    }, /"resource_key"/);
});

test('Should pass request_parameters to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{
                request_parameters: {
                    name: 'request_parameters',
                    type: 'collection',
                    value: [
                        {
                            name: 'ids',
                            value: 1,
                        },
                    ],
                },
            }}
        />
    );

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        options: {
            ids: 1,
        },
    }));
});

test('Should pass request_parameters and dataPathToAutoComplete to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    formInspector.getValueByPath.mockReturnValue(5);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{
                data_path_to_auto_complete: {
                    name: 'data_path_to_auto_complete',
                    value: [
                        {name: 'id', value: 'accountId'},
                    ],
                },
                request_parameters: {
                    name: 'request_parameters',
                    type: 'collection',
                    value: [
                        {
                            name: 'ids',
                            value: 1,
                        },
                    ],
                },
            }}
        />
    );

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        options: {
            ids: 1,
            accountId: 5,
        },
    }));
});

test('Should pass same request_parameters and dataPathToAutoComplete options to auto_complete options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    formInspector.getValueByPath.mockReturnValue(5);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{
                data_path_to_auto_complete: {
                    name: 'data_path_to_auto_complete',
                    value: [
                        {name: 'id', value: 'accountId'},
                    ],
                },
                request_parameters: {
                    name: 'request_parameters',
                    type: 'collection',
                    value: [
                        {
                            name: 'accountId',
                            value: 1,
                        },
                    ],
                },
            }}
        />
    );

    expect(mockSingleAutoCompleteProps).toEqual(expect.objectContaining({
        options: {
            accountId: 1,
        },
    }));
});
