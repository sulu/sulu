// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';
import ResourceStore from '../../../../stores/ResourceStore';
import Datagrid from '../../../Datagrid';
import Selection from '../../fields/Selection';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';

jest.mock('../../../Datagrid', () => jest.fn(() => null));

jest.mock('../../../Datagrid/stores/DatagridStore',
    () => function(resourceKey, userSettingsKey, observableOptions = {}, options, initialSelectionIds) {
        this.resourceKey = resourceKey;
        this.userSettingsKey = userSettingsKey;
        this.locale = observableOptions.locale;
        this.initialSelectionIds = initialSelectionIds;
        this.dataLoading = true;

        mockExtendObservable(this, {
            selectionIds: [],
        });
    }
);

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.resourceKey = formStore.resourceKey;
    this.locale = formStore.locale;
}));
jest.mock('../../stores/FormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.locale = resourceStore.locale;
}));
jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, options) {
    this.id = id;
    this.resourceKey = resourceKey;
    this.locale = options ? options.locale : undefined;
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should pass props correctly to selection component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'snippets',
        types: {
            datagrid_overlay: {
                adapter: 'table',
                display_properties: ['id', 'title'],
                icon: '',
                label: 'sulu_snippet.selection_label',
                overlay_title: 'sulu_snippet.selection_overlay_title',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(translate).toBeCalledWith('sulu_snippet.selection_label', {count: 3});

    expect(selection.find('MultiSelection').props()).toEqual(expect.objectContaining({
        adapter: 'table',
        disabled: true,
        displayProperties: ['id', 'title'],
        label: 'sulu_snippet.selection_label',
        locale,
        resourceKey: 'snippets',
        overlayTitle: 'sulu_snippet.selection_overlay_title',
        value,
    }));
});

test('Should pass id of form as disabledId to overlay type to avoid assigning something to itself', () => {
    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'pages',
        types: {
            datagrid_overlay: {
                adapter: 'table',
            },
        },
    };

    const formInspector = new FormInspector(new FormStore(new ResourceStore('pages', 4)));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(selection.find('MultiSelection').prop('disabledIds')).toEqual([4]);
});

test('Should pass empty array if value is not given to overlay type', () => {
    const changeSpy = jest.fn();
    const fieldOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'pages',
        types: {
            datagrid_overlay: {
                adapter: 'column_list',
                label: 'sulu_content.selection_label',
            },
        },
    };
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
        />
    );

    expect(translate).toBeCalledWith('sulu_content.selection_label', {count: 0});
    expect(selection.find('MultiSelection').props()).toEqual(expect.objectContaining({
        adapter: 'column_list',
        resourceKey: 'pages',
        value: [],
    }));
});

test('Should call onChange and onFinish callback when selection overlay is confirmed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'pages',
        types: {
            datagrid_overlay: {
                adapter: 'column_list',
                label: 'sulu_content.selection_label',
            },
        },
    };
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.find('MultiSelection').prop('onChange')([1, 2, 3]);

    expect(changeSpy).toBeCalledWith([1, 2, 3]);
    expect(finishSpy).toBeCalledWith();
});

test('Should throw an error if no "resource_key" option is passed in fieldOptions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{default_type: 'datagrid_overlay'}}
            formInspector={formInspector}
        />
    )).toThrowError(/"resource_key"/);
});

test('Should throw an error if no "adapter" option is passed for overlay type in fieldTypeOptions', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));
    const fieldTypeOptions = {
        default_type: 'datagrid_overlay',
        resource_key: 'test',
        types: {
            datagrid_overlay: {},
        },
    };

    expect(() => shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    )).toThrowError(/"adapter"/);
});

test('Should call the disposer for datagrid selections if unmounted', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('snippets')));
    const fieldTypeOptions = {
        default_type: 'datagrid',
        resource_key: 'test',
        types: {
            datagrid: {
                adapter: 'tree_table',
            },
        },
    };

    const selection = mount(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    const changeDatagridDisposerSpy = jest.fn();
    selection.instance().changeDatagridDisposer = changeDatagridDisposerSpy;

    selection.unmount();

    expect(changeDatagridDisposerSpy).toBeCalledWith();
});

test('Should pass props correctly to datagrid component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
        default_type: 'datagrid',
        resource_key: 'snippets',
        types: {
            datagrid: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.instance().datagridStore.resourceKey).toEqual('snippets');
    expect(selection.instance().datagridStore.initialSelectionIds).toEqual(value);
    expect(selection.find(Datagrid).props()).toEqual(expect.objectContaining({
        adapters: ['table'],
        searchable: false,
    }));
});

test('Should call onChange and onFinish prop when datagrid selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'datagrid',
        resource_key: 'snippets',
        types: {
            datagrid: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().datagridStore.dataLoading = false;
    selection.instance().datagridStore.selectionIds = [1, 5, 7];

    expect(changeSpy).toBeCalledWith([1, 5, 7]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not call onChange and onFinish prop while datagrid is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const fieldTypeOptions = {
        default_type: 'datagrid',
        resource_key: 'snippets',
        types: {
            datagrid: {
                adapter: 'table',
            },
        },
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    selection.instance().datagridStore.selectionIds = [1, 5, 7];

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should pass props correctly to MultiAutoComplete component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
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
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: false,
        disabled: true,
        displayProperty: 'name',
        filterParameter: 'names',
        idProperty: 'uuid',
        locale,
        resourceKey: 'snippets',
        searchProperties: ['name'],
        value,
    }));
});

test('Should pass allowAdd prop to MultiAutoComplete component', () => {
    const value = [1, 6, 8];

    const fieldTypeOptions = {
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
    };

    const locale = observable.box('en');

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('pages', 1, {locale})
        )
    );

    const selection = shallow(
        <Selection
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(selection.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        allowAdd: true,
    }));
});
