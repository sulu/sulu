/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import log from 'loglevel';
import {act, render, waitFor} from '@testing-library/react';
import {observable, extendObservable as mockExtendObservable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import SingleSelectionStore from '../../../../stores/SingleSelectionStore';
import userStore from '../../../../stores/userStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelection from '../../fields/SingleSelection';
import ResourceSingleSelect from '../../../../containers/ResourceSingleSelect';
import SingleAutoComplete from '../../../../containers/SingleAutoComplete';
import SingleSelectionComponent from '../../../../containers/SingleSelection';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

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
    this.formKey = formKey;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options || {};
    this.getValueByPath = jest.fn();
    this.addFinishFieldHandler = jest.fn();
}));

jest.mock('../../../../containers/ResourceSingleSelect', () => jest.fn(() => null));
jest.mock('../../../../containers/SingleAutoComplete', () => jest.fn(() => null));
jest.mock('../../../../containers/SingleSelection', () => jest.fn(() => null));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const ResourceSingleSelectMock = ResourceSingleSelect;
const SingleAutoCompleteMock = SingleAutoComplete;
const SingleSelectionComponentMock = SingleSelectionComponent;
const userStoreMock = userStore;

function createFormInspector(resourceKey = 'test', id = undefined, locale = undefined, options = {}) {
    return new FormInspector(new ResourceFormStore(new ResourceStore(resourceKey, id, locale), 'test', options));
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('passes correct props and SingleSelectionStore to SingleAutoComplete', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector('test', undefined, locale);
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

    const {asFragment} = render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="entity-id"
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('accounts', 'entity-id', locale);
    expect(getLatestMockProps(SingleAutoCompleteMock)).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        options: {},
        searchProperties: ['name', 'number'],
        selectionStore: expect.anything(),
    }));
    expect(asFragment()).toMatchSnapshot();
});

test('passes deprecated data_path_to_auto_complete values to options and logs warning', () => {
    const formInspector = createFormInspector('test');
    formInspector.getValueByPath.mockReturnValue(5);

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
                data_path_to_auto_complete: {
                    name: 'data_path_to_auto_complete',
                    value: [{name: 'id', value: 'accountId'}],
                },
            }}
            value="entity-id"
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/id');
    expect(getLatestMockProps(SingleAutoCompleteMock).options).toEqual({accountId: 5});
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "data_path_to_auto_complete" option is deprecated'));
});

test('uses locale from userStore if form has no locale', () => {
    const formInspector = createFormInspector('test', undefined, undefined);
    userStoreMock.contentLocale = 'en';

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
            value="entity-id"
        />
    );

    const props = getLatestMockProps(SingleAutoCompleteMock);
    expect(props.selectionStore.resourceKey).toEqual('accounts');
    expect(props.selectionStore.item).toEqual({id: 'entity-id'});
    expect(props.selectionStore.locale.get()).toEqual('en');
});

test('calls onChange and onFinish when auto_complete selection changes', () => {
    const formInspector = createFormInspector('test');
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

    const ref = React.createRef();

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            ref={ref}
            value="entity-id"
        />
    );

    act(() => {
        ref.current.autoCompleteSelectionStore.item = {id: 'new-entity-id'};
    });

    expect(changeSpy).toBeCalledWith('new-entity-id');
    expect(finishSpy).toBeCalled();
});

test('throws error if auto_complete configuration was omitted', () => {
    const formInspector = createFormInspector('test');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'auto_complete',
                resource_key: 'accounts',
                types: {},
            }}
            formInspector={formInspector}
        />
    )).toThrow(/"auto_complete"/);
});

test('passes correct props to ResourceSingleSelect', () => {
    const formInspector = createFormInspector('test');

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                default_type: 'single_select',
                resource_key: 'accounts',
                types: {
                    single_select: {
                        display_property: 'name',
                        id_property: 'id',
                        overlay_title: 'sulu_contact.overlay_title',
                    },
                },
            }}
            formInspector={formInspector}
            schemaOptions={{
                editable: {
                    value: true,
                },
            }}
            value={3}
        />
    );

    expect(getLatestMockProps(ResourceSingleSelectMock)).toEqual(expect.objectContaining({
        disabled: true,
        displayProperty: 'name',
        editable: true,
        idProperty: 'id',
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'accounts',
        value: 3,
    }));
});

test('calls onChange and onFinish when single select changes', () => {
    const formInspector = createFormInspector('test');
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'single_select',
                resource_key: 'accounts',
                types: {
                    single_select: {
                        display_property: 'name',
                        id_property: 'id',
                        overlay_title: 'sulu_contact.overlay_title',
                    },
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={3}
        />
    );

    getLatestMockProps(ResourceSingleSelectMock).onChange(7);
    expect(changeSpy).toBeCalledWith(7);
    expect(finishSpy).toBeCalled();
});

test('throws error if display_property for single_select is missing', () => {
    const formInspector = createFormInspector('test');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'single_select',
                resource_key: 'accounts',
                types: {
                    single_select: {
                        id_property: 'id',
                    },
                },
            }}
            formInspector={formInspector}
        />
    )).toThrow('The "display_property" field-type option must be a string!');
});

test('throws error if id_property for single_select is missing', () => {
    const formInspector = createFormInspector('test');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'single_select',
                resource_key: 'accounts',
                types: {
                    single_select: {
                        display_property: 'title',
                    },
                },
            }}
            formInspector={formInspector}
        />
    )).toThrow('The "id_property" field-type option must be a string!');
});

test('passes correct props to list overlay component', () => {
    const locale = observable.box('de');
    const formInspector = createFormInspector('contacts', 10, locale, {testOption: 'foo'});

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'contacts',
                types: {
                    list_overlay: {
                        adapter: 'table',
                        display_properties: ['name'],
                        empty_text: 'sulu_contact.nothing',
                        icon: 'su-account',
                        list_key: 'accounts_list',
                        overlay_title: 'sulu_contact.overlay_title',
                        detail_options: {foo: 'bar'},
                    },
                },
            }}
            formInspector={formInspector}
            schemaOptions={{
                allow_deselect_for_disabled_items: {
                    value: true,
                },
                item_disabled_condition: {
                    value: 'id == 10',
                },
                request_parameters: {
                    value: [{name: 'foo', value: 'bar'}],
                },
                resource_store_properties_to_request: {
                    value: [{name: 'owner', value: 'ownerId'}],
                },
            }}
            value={4}
        />
    );

    expect(formInspector.getValueByPath).toBeCalledWith('/ownerId');

    expect(getLatestMockProps(SingleSelectionComponentMock)).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: true,
        detailOptions: {foo: 'bar', owner: undefined},
        disabled: true,
        disabledIds: [10],
        displayProperties: ['name'],
        emptyText: 'sulu_contact.nothing',
        icon: 'su-account',
        itemDisabledCondition: 'id == 10',
        listKey: 'accounts_list',
        listOptions: {foo: 'bar', owner: undefined},
        locale,
        overlayTitle: 'sulu_contact.overlay_title',
        resourceKey: 'contacts',
        value: 4,
    }));
});

test('uses resourceKey as listKey if list_key is not configured', () => {
    const formInspector = createFormInspector('test');

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
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
            }}
            formInspector={formInspector}
            value={null}
        />
    );

    expect(getLatestMockProps(SingleSelectionComponentMock).listKey).toEqual('accounts');
    expect(getLatestMockProps(SingleSelectionComponentMock).value).toEqual(null);
});

test('updates list options when observed form property changes', async() => {
    const formInspector = createFormInspector('test', undefined, observable.box('en'));
    formInspector.getValueByPath
        .mockReturnValueOnce(1)
        .mockReturnValueOnce(2);

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
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
            }}
            formInspector={formInspector}
            schemaOptions={{
                resource_store_properties_to_request: {
                    value: [{name: 'accountId', value: 'id'}],
                },
            }}
            value={4}
        />
    );

    const finishHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    expect(getLatestMockProps(SingleSelectionComponentMock).listOptions).toEqual({accountId: 1});

    act(() => {
        finishHandler('/id');
    });

    await waitFor(() => {
        expect(getLatestMockProps(SingleSelectionComponentMock).listOptions).toEqual({accountId: 2});
    });
});

test('navigates when list overlay item is clicked with configured view', () => {
    const formInspector = createFormInspector('test');
    const router = new Router();

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                view: {
                    name: 'sulu_contact.contact_edit_form',
                    result_to_view: {
                        id: 'id',
                        locale: 'locale',
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
            }}
            formInspector={formInspector}
            router={router}
            value={4}
        />
    );

    getLatestMockProps(SingleSelectionComponentMock).onItemClick(7, {id: 7, locale: 'en'});

    expect(router.navigate).toBeCalledWith('sulu_contact.contact_edit_form', {id: 7, locale: 'en'});
});

test('does nothing when list overlay item is clicked without router', () => {
    const formInspector = createFormInspector('test');

    render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
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
            }}
            formInspector={formInspector}
            value={4}
        />
    );

    expect(getLatestMockProps(SingleSelectionComponentMock).onItemClick).toBeFalsy();
});

test('throws for invalid schema option types', () => {
    const formInspector = createFormInspector('test');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                form_options_to_list_options: {value: 'foo'},
            }}
        />
    )).toThrow('The "form_options_to_list_options" option has to be an array if defined!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                request_parameters: {value: 'foo'},
            }}
        />
    )).toThrow('The "request_parameters" schemaOption must be an array!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                resource_store_properties_to_request: {value: 'foo'},
            }}
        />
    )).toThrow('The "resource_store_properties_to_request" schemaOption must be an array!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                item_disabled_condition: {value: true},
            }}
        />
    )).toThrow('The "item_disabled_condition" schema option must be a string if given!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                allow_deselect_for_disabled_items: {value: 'foo'},
            }}
        />
    )).toThrow('The "allow_deselect_for_disabled_items" schema option must be a boolean if given!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {
                    list_overlay: {
                        adapter: 'table',
                        detail_options: 'foo',
                        display_properties: ['name'],
                    },
                },
            }}
            formInspector={formInspector}
        />
    )).toThrow('The "detail_options" option has to be an array if defined!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                type: {value: 7},
            }}
        />
    )).toThrow('The "type" schema option must be a string!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 7,
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
        />
    )).toThrow('The "default_type" field-type option must be a string!');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
        />
    )).toThrow('The selection field needs a "resource_key" option to work properly');

    expect(() => render(
        <SingleSelection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'list_overlay',
                resource_key: 'accounts',
                types: {list_overlay: {adapter: 'table', display_properties: ['name']}},
            }}
            formInspector={formInspector}
            schemaOptions={{
                types: {value: false},
            }}
        />
    )).toThrow('The "types" schema option must be a string if given!');
});

test('passes request parameters to auto_complete options and requestOptions win on duplicate keys', () => {
    const formInspector = createFormInspector('test');
    formInspector.getValueByPath.mockReturnValue(7);

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
                    value: [{name: 'id', value: 9}],
                },
                data_path_to_auto_complete: {
                    value: [{name: 'id', value: 'id'}],
                },
            }}
            value="entity-id"
        />
    );

    expect(getLatestMockProps(SingleAutoCompleteMock).options).toEqual({id: 9});
});
