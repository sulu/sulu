/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import log from 'loglevel';
import {act, render, waitFor} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable, toJS} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';
import MultiSelectionStore from '../../../../stores/MultiSelectionStore';
import ResourceStore from '../../../../stores/ResourceStore';
import userStore from '../../../../stores/userStore';
import Router from '../../../../services/Router';
import List from '../../../List';
import ListStore from '../../../List/stores/ListStore';
import Selection from '../../fields/Selection';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import MultiAutoComplete from '../../../../containers/MultiAutoComplete';
import MultiSelectionComponent from '../../../../containers/MultiSelection';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(
    function(resourceKey, selectedItemIds, locale, idFilterParameter) {
        this.idFilterParameter = idFilterParameter;
        this.loading = false;
        this.loadItems = jest.fn();
        this.locale = locale;
        this.resourceKey = resourceKey;

        mockExtendObservable(this, {
            items: selectedItemIds.map((id) => ({id, uuid: id})),
        });
    })
);

jest.mock('../../../../services/Router', () => jest.fn(() => ({
    navigate: jest.fn(),
})));

jest.mock('../../../List', () => jest.fn(() => <div data-testid="list" />));

jest.mock('../../../List/stores/ListStore', () => jest.fn(
    function(
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions = {},
        options,
        metadataOptions,
        initialSelectionIds
    ) {
        this.clearSelection = jest.fn();
        this.dataLoading = true;
        this.destroy = jest.fn();
        this.initialSelectionIds = initialSelectionIds;
        this.listKey = listKey;
        this.loading = false;
        this.locale = observableOptions.locale;
        this.metadataOptions = metadataOptions;
        this.observableOptions = observableOptions;
        this.options = options;
        this.resourceKey = resourceKey;
        this.reset = jest.fn();
        this.select = jest.fn();
        this.sendRequestDisposer = jest.fn();
        this.userSettingsKey = userSettingsKey;

        mockExtendObservable(this, {
            selectionIds: [],
        });
    }
));

jest.mock('../../../../stores/userStore', () => ({}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.addFinishFieldHandler = jest.fn();
    this.getValueByPath = jest.fn();
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.resourceKey = formStore.resourceKey;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.resourceKey = resourceStore.resourceKey;
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, options) {
    this.id = id;
    this.locale = options ? options.locale : undefined;
    this.resourceKey = resourceKey;
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/MultiAutoComplete', () => jest.fn(() => <div data-testid="multi-auto-complete" />));
jest.mock('../../../../containers/MultiSelection', () => jest.fn(() => <div data-testid="multi-selection" />));

const ListMock = List;
const ListStoreMock = ListStore;
const MultiAutoCompleteMock = MultiAutoComplete;
const MultiSelectionComponentMock = MultiSelectionComponent;
const MultiSelectionStoreMock = MultiSelectionStore;
const userStoreMock = userStore;

function createFormInspector(resourceKey = 'pages', id = 1, locale = observable.box('en')) {
    return new FormInspector(
        new ResourceFormStore(
            new ResourceStore(resourceKey, id, {locale}),
            resourceKey
        )
    );
}

function renderSelection(props = {}) {
    const ref = React.createRef();
    const formInspector = props.formInspector || createFormInspector();
    const fieldTypeOptions = props.fieldTypeOptions || {
        default_type: 'list_overlay',
        resource_key: 'snippets',
        types: {
            list_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const view = render(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={props.onChange || jest.fn()}
            onFinish={props.onFinish || jest.fn()}
            ref={ref}
            {...props}
        />
    );

    return {
        ...view,
        formInspector,
        ref,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('renders list overlay mode and matches snapshot', () => {
    const value = [1, 6, 8];

    const {asFragment} = renderSelection({value});

    expect(getLatestMockProps(MultiSelectionComponentMock).value).toEqual(value);
    expect(asFragment()).toMatchSnapshot();
});

test('passes correct props to MultiSelection component', () => {
    const value = [1, 6, 8];
    const locale = observable.box('en');
    const formInspector = createFormInspector('pages', 1, locale);

    renderSelection({
        disabled: true,
        formInspector,
        schemaOptions: {
            types: {
                name: 'types',
                value: 'test',
            },
        },
        value,
    });

    expect(translate).toBeCalledWith('sulu_snippet.selection_label', {count: 3});
    expect(getLatestMockProps(MultiSelectionComponentMock)).toEqual(expect.objectContaining({
        adapter: 'table',
        allowDeselectForDisabledItems: true,
        disabled: true,
        displayProperties: ['id', 'title'],
        itemDisabledCondition: undefined,
        label: 'sulu_snippet.selection_label',
        listKey: 'snippets',
        locale,
        options: {types: 'test'},
        overlayTitle: 'sulu_snippet.selection_overlay_title',
        resourceKey: 'snippets',
        sortable: true,
        value,
    }));
});

test('passes resourceKey as listKey if listKey is missing', () => {
    renderSelection({value: [1]});

    expect(getLatestMockProps(MultiSelectionComponentMock).listKey).toEqual('snippets');
});

test('uses locale from userStore if form has no locale', () => {
    userStoreMock.contentLocale = 'de';

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    renderSelection({formInspector, value: [1, 2]});

    expect(toJS(getLatestMockProps(MultiSelectionComponentMock).locale)).toEqual('de');
});

test('passes schema options correctly to MultiSelection component', () => {
    const value = [1, 6, 8];
    const locale = observable.box('en');
    const formInspector = createFormInspector('pages', 1, locale);

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    renderSelection({
        disabled: true,
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
                list_overlay: {
                    adapter: 'table',
                    display_properties: ['id', 'title'],
                    label: 'sulu_snippet.selection_label',
                    overlay_title: 'sulu_snippet.selection_overlay_title',
                },
            },
        },
        formInspector,
        schemaOptions: {
            allow_deselect_for_disabled_items: {
                name: 'allow_deselect_for_disabled_items',
                value: false,
            },
            item_disabled_condition: {
                name: 'item_disabled_condition',
                value: 'status == "inactive"',
            },
            request_parameters: {
                name: 'request_parameters',
                value: [{name: 'staticKey', value: 'some-static-value'}],
            },
            resource_store_properties_to_request: {
                name: 'resource_store_properties_to_request',
                value: [{name: 'dynamicKey', value: 'otherPropertyName'}],
            },
            sortable: {
                name: 'sortable',
                value: false,
            },
            type: {
                name: 'type',
                value: 'list_overlay',
            },
            types: {
                name: 'types',
                value: 'image,video',
            },
        },
        value,
    });

    expect(formInspector.getValueByPath).toBeCalledWith('/otherPropertyName');
    expect(getLatestMockProps(MultiSelectionComponentMock)).toEqual(expect.objectContaining({
        allowDeselectForDisabledItems: false,
        itemDisabledCondition: 'status == "inactive"',
        options: {
            dynamicKey: 'value-returned-by-form-inspector',
            staticKey: 'some-static-value',
            types: 'image,video',
        },
        sortable: false,
    }));
});

test('updates MultiSelection options when observed form value changes', async() => {
    const formInspector = createFormInspector();
    const values = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => values[path]);

    renderSelection({
        formInspector,
        schemaOptions: {
            resource_store_properties_to_request: {
                name: 'resource_store_properties_to_request',
                value: [{name: 'dynamicKey', value: 'otherPropertyName'}],
            },
        },
        value: [1],
    });

    expect(getLatestMockProps(MultiSelectionComponentMock).options).toEqual({dynamicKey: 'first-value'});

    values['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    act(() => {
        finishFieldHandler('/otherPropertyName');
    });

    await waitFor(() => {
        expect(getLatestMockProps(MultiSelectionComponentMock).options).toEqual({dynamicKey: 'second-value'});
    });
});

test('passes current form id as disabledId when selecting same resource', () => {
    const formInspector = createFormInspector('pages', 4);

    renderSelection({
        fieldTypeOptions: {
            default_type: 'list_overlay',
            resource_key: 'pages',
            types: {
                list_overlay: {
                    adapter: 'table',
                },
            },
        },
        formInspector,
    });

    expect(getLatestMockProps(MultiSelectionComponentMock).disabledIds).toEqual([4]);
});

test('passes empty value array for list overlay if value is undefined', () => {
    renderSelection({
        fieldTypeOptions: {
            default_type: 'list_overlay',
            resource_key: 'pages',
            types: {
                list_overlay: {
                    adapter: 'column_list',
                    label: 'sulu_page.selection_label',
                },
            },
        },
        value: undefined,
    });

    expect(translate).toBeCalledWith('sulu_page.selection_label', {count: 0});
    expect(getLatestMockProps(MultiSelectionComponentMock).value).toEqual([]);
});

test('calls onChange and onFinish when MultiSelection onChange is fired', async() => {
    const onChange = jest.fn();
    const onFinish = jest.fn();

    renderSelection({onChange, onFinish, value: [1]});

    getLatestMockProps(MultiSelectionComponentMock).onChange([1, 2, 3]);

    expect(onChange).toBeCalledWith([1, 2, 3]);
    expect(onFinish).toBeCalled();
});

test('does not trigger navigation if no view is configured', () => {
    const router = new Router();
    renderSelection({router, value: [1, 2]});

    expect(getLatestMockProps(MultiSelectionComponentMock).onItemClick).toBeFalsy();
    expect(router.navigate).not.toBeCalled();
});

test('navigates to configured view when onItemClick is fired', () => {
    const router = new Router();

    renderSelection({
        fieldTypeOptions: {
            default_type: 'list_overlay',
            resource_key: 'pages',
            types: {
                list_overlay: {
                    adapter: 'column_list',
                    label: 'sulu_page.selection_label',
                },
            },
            view: {
                name: 'sulu_page.page_edit_form',
                result_to_view: {
                    'properties/locale': 'locale',
                    id: 'uuid',
                },
            },
        },
        router,
        value: [1],
    });

    getLatestMockProps(MultiSelectionComponentMock).onItemClick(1, {id: 1, properties: {locale: 'de'}});

    expect(router.navigate).toBeCalledWith('sulu_page.page_edit_form', {locale: 'de', uuid: 1});
});

test('logs warning and extracts ids if value is array of objects', () => {
    renderSelection({
        fieldTypeOptions: {
            default_type: 'list_overlay',
            resource_key: 'test',
            types: {
                list_overlay: {
                    adapter: 'table',
                },
            },
        },
        value: [{id: 55}, {id: 66}],
    });

    expect(getLatestMockProps(MultiSelectionComponentMock).value).toEqual([55, 66]);
    expect(log.warn).toBeCalledWith(expect.stringContaining('expects an array of ids as value'));
});

test('throws for invalid list overlay schema and field options', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        default_type: 'list_overlay',
        resource_key: 'test',
        types: {
            list_overlay: {},
        },
    };

    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {types: {name: 'types', value: []}},
    })).toThrow(/"types"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {item_disabled_condition: {name: 'item_disabled_condition', value: []}},
    })).toThrow(/"item_disabled_condition"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {allow_deselect_for_disabled_items: {name: 'allow_deselect_for_disabled_items', value: 'no'}},
    })).toThrow(/"allow_deselect_for_disabled_items"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {sortable: {name: 'sortable', value: 'no'}},
    })).toThrow(/"sortable"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {request_parameters: {name: 'request_parameters', value: 'no'}},
    })).toThrow(/"request_parameters"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {
            resource_store_properties_to_request: {name: 'resource_store_properties_to_request', value: 'no'},
        },
    })).toThrow(/"resource_store_properties_to_request"/);
    expect(() => renderSelection({
        fieldTypeOptions: {default_type: 'list_overlay'},
        formInspector,
    })).toThrow(/"resource_key"/);
    expect(() => renderSelection({fieldTypeOptions, formInspector})).toThrow(/"adapter"/);
    expect(() => renderSelection({
        fieldTypeOptions: {...fieldTypeOptions, default_type: true},
        formInspector,
    })).toThrow(/"default_type"/);
    expect(() => renderSelection({
        fieldTypeOptions,
        formInspector,
        schemaOptions: {type: {name: 'type', value: true}},
    })).toThrow(/"type"/);
});

test('calls list and auto-complete disposers and destroys listStore on unmount', () => {
    const {ref, unmount} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'test',
            types: {
                list: {
                    adapter: 'tree_table',
                },
            },
        },
    });

    const changeListDisposer = jest.fn();
    const changeLocaleDisposer = jest.fn();
    const changeListOptionsDisposer = jest.fn();
    const changeAutoCompleteSelectionDisposer = jest.fn();
    ref.current.changeListDisposer = changeListDisposer;
    ref.current.changeLocaleDisposer = changeLocaleDisposer;
    ref.current.changeListOptionsDisposer = changeListOptionsDisposer;
    ref.current.changeAutoCompleteSelectionDisposer = changeAutoCompleteSelectionDisposer;
    const listStoreDestroy = ref.current.listStore.destroy;

    unmount();

    expect(changeListDisposer).toBeCalled();
    expect(changeLocaleDisposer).toBeCalled();
    expect(changeListOptionsDisposer).toBeCalled();
    expect(changeAutoCompleteSelectionDisposer).toBeCalled();
    expect(listStoreDestroy).toBeCalled();
});

test('calls sendRequestDisposer when locale changes in list mode', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector('pages', 1, locale);

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        formInspector,
    });

    act(() => {
        locale.set('de');
    });

    expect(ref.current.listStore.sendRequestDisposer).toBeCalled();
});

test('passes correct props to List component in list mode', () => {
    renderSelection({
        disabled: true,
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        schemaOptions: {
            item_disabled_condition: {
                name: 'item_disabled_condition',
                value: 'status == "inactive"',
            },
        },
        value: [1, 6, 8],
    });

    expect(getLatestMockProps(ListMock)).toEqual(expect.objectContaining({
        adapters: ['table'],
        disabled: true,
        itemDisabledCondition: 'status == "inactive"',
        searchable: false,
        showColumnOptions: false,
    }));
});

test('initializes listStore with correct parameters', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector('pages', 1, locale);

    const formInspectorValues = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => formInspectorValues[path]);

    renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                    list_key: 'snippets_list',
                },
            },
        },
        formInspector,
        schemaOptions: {
            request_parameters: {
                name: 'request_parameters',
                value: [{name: 'staticKey', value: 'some-static-value'}],
            },
            resource_store_properties_to_request: {
                name: 'resource_store_properties_to_request',
                value: [{name: 'dynamicKey', value: 'otherPropertyName'}],
            },
        },
        value: [1, 6, 8],
    });

    expect(ListStoreMock).toBeCalled();
    const listStore = ListStoreMock.mock.instances[ListStoreMock.mock.instances.length - 1];
    expect(listStore.resourceKey).toEqual('snippets');
    expect(listStore.listKey).toEqual('snippets_list');
    expect(listStore.userSettingsKey).toEqual('selection');
    expect(listStore.initialSelectionIds).toEqual([1, 6, 8]);
    expect(listStore.options).toEqual({
        dynamicKey: 'value-returned-by-form-inspector',
        staticKey: 'some-static-value',
    });
});

test('uses resourceKey as listKey in list mode if list_key is missing', () => {
    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        value: [1],
    });

    expect(ref.current.listStore.listKey).toEqual('snippets');
});

test('uses userStore locale for list mode if form has no locale', () => {
    userStoreMock.contentLocale = 'en';
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        formInspector,
        value: [1],
    });

    expect(toJS(ref.current.listStore.locale)).toEqual('en');
});

test('calls onChange and onFinish when list selection changes and list is not loading', async() => {
    const onChange = jest.fn();
    const onFinish = jest.fn();

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        onChange,
        onFinish,
    });

    ref.current.listStore.dataLoading = false;

    act(() => {
        ref.current.listStore.selectionIds = [1, 5, 7];
    });

    await waitFor(() => {
        expect(onChange).toBeCalledWith([1, 5, 7]);
    });
    expect(onFinish).toBeCalled();
});

test('does not call onChange and onFinish while list is loading', () => {
    const onChange = jest.fn();
    const onFinish = jest.fn();

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                },
            },
        },
        onChange,
        onFinish,
    });

    act(() => {
        ref.current.listStore.selectionIds = [1, 5, 7];
    });

    expect(onChange).not.toBeCalled();
    expect(onFinish).not.toBeCalled();
});

test('updates listStore options when request options change', () => {
    const formInspector = createFormInspector();
    const values = {'/otherPropertyName': 'first-value'};
    formInspector.getValueByPath.mockImplementation((path) => values[path]);

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'list',
            resource_key: 'snippets',
            types: {
                list: {
                    adapter: 'table',
                    list_key: 'snippets_list',
                },
            },
        },
        formInspector,
        schemaOptions: {
            resource_store_properties_to_request: {
                name: 'resource_store_properties_to_request',
                value: [{name: 'dynamicKey', value: 'otherPropertyName'}],
            },
        },
        value: [1, 6, 8],
    });

    expect(ref.current.listStore.options).toEqual({dynamicKey: 'first-value'});

    ref.current.listStore.selectionIds = [12, 14];
    values['/otherPropertyName'] = 'second-value';
    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    act(() => {
        finishFieldHandler('/otherPropertyName');
    });

    expect(ref.current.listStore.options).toEqual({dynamicKey: 'second-value'});
    expect(ref.current.listStore.reset).toBeCalled();
    expect(ref.current.listStore.initialSelectionIds).toEqual([12, 14]);
});

test('passes correct props to MultiAutoComplete and initializes MultiSelectionStore', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector('pages', 1, locale);

    const {ref} = renderSelection({
        disabled: true,
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        formInspector,
        value: [1, 6, 8],
    });

    expect(MultiSelectionStoreMock).toBeCalledWith('snippets', [1, 6, 8], locale, 'names');
    expect(getLatestMockProps(MultiAutoCompleteMock)).toEqual(expect.objectContaining({
        allowAdd: undefined,
        disabled: true,
        displayProperty: 'name',
        idProperty: 'uuid',
        options: {},
        searchProperties: ['name'],
        selectionStore: ref.current.autoCompleteSelectionStore,
    }));
});

test('uses userStore locale for auto_complete mode if form has no locale', () => {
    userStoreMock.contentLocale = 'de';
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 1),
            'pages'
        )
    );

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        formInspector,
        value: [1],
    });

    expect(ref.current.autoCompleteSelectionStore.locale.get()).toEqual('de');
});

test('passes request options to MultiAutoComplete in auto_complete mode', () => {
    const formInspector = createFormInspector();
    const values = {'/otherPropertyName': 'value-returned-by-form-inspector'};
    formInspector.getValueByPath.mockImplementation((path) => values[path]);

    renderSelection({
        disabled: true,
        fieldTypeOptions: {
            default_type: 'list_overlay',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
                list_overlay: {
                    adapter: 'table',
                    display_properties: ['id', 'title'],
                    label: 'sulu_snippet.selection_label',
                    overlay_title: 'sulu_snippet.selection_overlay_title',
                },
            },
        },
        formInspector,
        schemaOptions: {
            request_parameters: {
                name: 'request_parameters',
                value: [{name: 'staticKey', value: 'some-static-value'}],
            },
            resource_store_properties_to_request: {
                name: 'resource_store_properties_to_request',
                value: [{name: 'dynamicKey', value: 'otherPropertyName'}],
            },
            type: {
                name: 'type',
                value: 'auto_complete',
            },
        },
        value: [1, 6, 8],
    });

    expect(formInspector.getValueByPath).toBeCalledWith('/otherPropertyName');
    expect(getLatestMockProps(MultiAutoCompleteMock).options).toEqual({
        dynamicKey: 'value-returned-by-form-inspector',
        staticKey: 'some-static-value',
    });
});

test('triggers auto_complete item reload when value changes', () => {
    const onChange = jest.fn();
    const onFinish = jest.fn();
    const {formInspector, ref, rerender} = renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        value: [1, 6, 8],
    });

    ref.current.autoCompleteSelectionStore.items = [{uuid: 1}, {uuid: 6}, {uuid: 8}];

    rerender(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'auto_complete',
                resource_key: 'snippets',
                types: {
                    auto_complete: {
                        display_property: 'name',
                        filter_parameter: 'names',
                        id_property: 'uuid',
                        search_properties: ['name'],
                    },
                },
            }}
            formInspector={formInspector}
            onChange={onChange}
            onFinish={onFinish}
            ref={ref}
            value={[3, 4, 7]}
        />
    );

    expect(ref.current.autoCompleteSelectionStore.loadItems).toBeCalledWith([3, 4, 7]);
});

test('does not reload auto_complete items when value stays the same', () => {
    const onChange = jest.fn();
    const onFinish = jest.fn();
    const {formInspector, ref, rerender} = renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        value: [1, 6, 8],
    });

    ref.current.autoCompleteSelectionStore.items = [{uuid: 1}, {uuid: 6}, {uuid: 8}];

    rerender(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                default_type: 'auto_complete',
                resource_key: 'snippets',
                types: {
                    auto_complete: {
                        display_property: 'name',
                        filter_parameter: 'names',
                        id_property: 'uuid',
                        search_properties: ['name'],
                    },
                },
            }}
            formInspector={formInspector}
            onChange={onChange}
            onFinish={onFinish}
            ref={ref}
            value={[1, 6, 8]}
        />
    );

    expect(ref.current.autoCompleteSelectionStore.loadItems).not.toBeCalled();
});

test('calls onChange and onFinish when auto_complete selection store items change', async() => {
    const onChange = jest.fn();
    const onFinish = jest.fn();

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'pages',
            types: {
                auto_complete: {
                    allow_add: true,
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        onChange,
        onFinish,
    });

    act(() => {
        ref.current.autoCompleteSelectionStore.items = [{uuid: 1}, {uuid: 2}, {uuid: 3}];
    });

    await waitFor(() => {
        expect(onChange).toBeCalledWith([1, 2, 3]);
    });
    expect(onFinish).toBeCalled();
});

test('does not call onChange/onFinish when auto_complete selection is empty and value is undefined', () => {
    const onChange = jest.fn();
    const onFinish = jest.fn();

    const {ref} = renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'pages',
            types: {
                auto_complete: {
                    allow_add: true,
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        onChange,
        onFinish,
        value: undefined,
    });

    act(() => {
        ref.current.autoCompleteSelectionStore.items = [];
    });

    expect(onChange).not.toBeCalled();
    expect(onFinish).not.toBeCalled();
});

test('passes allowAdd prop to MultiAutoComplete', () => {
    renderSelection({
        fieldTypeOptions: {
            default_type: 'auto_complete',
            resource_key: 'snippets',
            types: {
                auto_complete: {
                    allow_add: true,
                    display_property: 'name',
                    filter_parameter: 'names',
                    id_property: 'uuid',
                    search_properties: ['name'],
                },
            },
        },
        value: [1, 6, 8],
    });

    expect(getLatestMockProps(MultiAutoCompleteMock).allowAdd).toEqual(true);
});
