// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import userStore from '../../../../stores/userStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelection from '../../fields/SingleSelection';
import SingleSelectionComponent from '../../../../containers/SingleSelection';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, locale) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = locale;
}));

jest.mock('../../../../stores/userStore', () => jest.fn());

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
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass correct props to SingleAutoComplete', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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
        options: {},
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
        value,
    }));
});

test('Pass correct options to SingleAutoComplete based on data_path_to_auto_complete schema option', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    const schemaOptions = {
        data_path_to_auto_complete: {
            name: 'data_path_to_auto_complete',
            value: [
                {name: 'id', value: 'accountId'},
            ],
        },
    };

    formInspector.getValueByPath.mockReturnValue(5);

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

    expect(formInspector.getValueByPath).toBeCalledWith('/id');
    expect(singleSelection.find('SingleAutoComplete').props()).toEqual(expect.objectContaining({
        options: {
            accountId: 5,
        },
    }));
});

test('Pass correct props with schema-options type to SingleAutoComplete', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = {
        test: 'value',
    };

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

test('Pass null as value to SingleSelection for list_overlay', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const value = null;

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

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(singleSelection.find('SingleSelection').prop('value')).toEqual(expect.objectContaining(value));
});

test('Call onChange and onFinish when SingleAutoComplete changes', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{editable: {name: 'editable', value: true}}}
            value={value}
        />
    );

    expect(singleSelection.find('ResourceSingleSelect').props()).toEqual(expect.objectContaining({
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

    singleSelection.find('ResourceSingleSelect').simulate('change', 2);

    expect(changeSpy).toBeCalledWith(2);
    expect(finishSpy).toBeCalledWith();
});

test('Throw an error if no display_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        types: {
            single_select: {
            },
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"display_property"/);
});

test('Throw an error if no id_property is passed to the the single_select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const fieldTypeOptions = {
        default_type: 'single_select',
        types: {
            single_select: {
                display_property: 'something',
            },
        },
    };

    expect(
        () => shallow(
            <SingleSelection
                {...fieldTypeDefaultProps}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"id_property"/);
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
        allowDeselectForDisabledItems: true,
        listKey: 'accounts_list',
        detailOptions: undefined,
        disabled: true,
        disabledIds: [],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: undefined,
        listOptions: undefined,
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

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).prop('listKey')).toEqual('accounts');
});

test('Throw an error if form_options_to_list_options schema option is not an array', () => {
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

    const schemaOptions = {
        form_options_to_list_options: {
            name: 'form_options_to_api',
            value: 'test',
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"form_options_to_list_options"');
});

test('Throw an error if item_disabled_condition schema option is not a string', () => {
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

    const schemaOptions = {
        item_disabled_condition: {
            name: 'item_disabled_condition',
            value: [],
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"item_disabled_condition"');
});

test('Throw an error if allow_deselect_for_disabled_items schema option is not a boolean', () => {
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

    const schemaOptions = {
        allow_deselect_for_disabled_items: {
            name: 'allow_deselect_for_disabled_items',
            value: 'not-boolean',
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    )).toThrow('"allow_deselect_for_disabled_items"');
});

test('Throw an error if detail_options has wrong value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const value = 3;

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

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    )).toThrow('"detail_options"');
});

test('Pass correct props with schema-options type to SingleItemSelection', () => {
    const options = {
        segment: 'developer',
        webspace: 'sulu',
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test', options));

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
        allowDeselectForDisabledItems: false,
        detailOptions: {
            'ghost-content': true,
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
        },
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value,
    }));
});

test('Throw an error if a none string was passed to schema-options', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
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

test('Pass content locale from user to SingleItemSelection if form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('accounts', 5), 'test'));

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

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).prop('locale').get()).toEqual('en');
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

    const singleSelection = shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(singleSelection.find(SingleSelectionComponent).props()).toEqual(expect.objectContaining({
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

test('Should throw an error if "types" schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'pages'));
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => shallow(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            schemaOptions={{types: {name: 'types', value: []}}}
        />
    )).toThrowError(/"types"/);
});
