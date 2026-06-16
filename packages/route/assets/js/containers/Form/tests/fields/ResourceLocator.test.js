// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Requester from 'sulu-admin-bundle/services/Requester';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import userStore from 'sulu-admin-bundle/stores/userStore';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({}));

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.locale = observableOptions.locale;

        mockExtendObservable(this, {
            data: {},
        });
    })
);

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options) {
        this.resourceKey = resourceStore.resourceKey;
        this.id = resourceStore.id;
        this.locale = resourceStore.locale;
        this.options = options || {};
        this.resourceStore = resourceStore;
    })
);

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.resourceKey = formStore.resourceKey;
    this.errors = {};
    this.addFinishFieldHandler = jest.fn();
    this.getPathsByTag = jest.fn().mockReturnValue([]);
    this.getValueByPath = jest.fn((path) => formStore.resourceStore.data[path]);
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({});
    this.isFieldModified = jest.fn().mockReturnValue(false);
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    post: jest.fn(),
}));

test('Pass props correctly to ResourceLocator', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore(
                'test',
                undefined,
                {'locale': observable.box('en')}
            ),
            'test'
        )
    );

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_full_edit',
            }}
            formInspector={formInspector}
            value="/url"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/url');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('tree_full_edit');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('disabled')).toBe(true);
    expect(resourceLocator.find(ResourceLocatorComponent).prop('locale').get()).toBe('en');

    // should not throw any error on unmount
    resourceLocator.unmount();
});

test('Render just slash instead of ResourceLocatorComponent if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find(ResourceLocatorComponent)).toHaveLength(0);
    expect(resourceLocator.text()).toEqual('/');

    // should not throw any error on unmount
    resourceLocator.unmount();
});

test('Pass correct options to ResourceLocatorHistory if resource already existed', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {webspace: 'sulu'})
    );

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                historyResourceKey: 'route-histories',
                defaultMode: 'tree_leaf_edit',
                options: {history: true},
            }}
            formInspector={formInspector}
            schemaOptions={{}}
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('ResourceLocatorHistory')).toHaveLength(1);
    expect(resourceLocator.find('ResourceLocatorHistory').prop('options'))
        .toEqual({history: true, webspace: 'sulu', resourceId: 1, resourceKey: 'test'});
    expect(resourceLocator.find('ResourceLocatorHistory').prop('resourceKey')).toEqual('route-histories');
    expect(resourceLocator.find('ResourceLocatorHistory').prop('disabled')).toEqual(false);
});

test('Pass locale from userStore to ResourceLocator and ResourceLocatorHistory if form has no locale', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', 1, {'locale': undefined}),
            'test',
            {webspace: 'sulu'}
        )
    );

    // $FlowFixMe
    userStore.contentLocale = 'cz';

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                historyResourceKey: 'route-histories',
                defaultMode: 'tree_full_edit',
                options: {history: true},
            }}
            formInspector={formInspector}
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('locale').get()).toBe('cz');
    expect(resourceLocator.find('ResourceLocatorHistory').prop('options').locale).toBe('cz');
});

test('Do not add an addFinishFieldHandler for URL generation if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toHaveBeenCalled();
});

test('Do not add an addFinishFieldHandler for URL generation if no generationUrl was passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toHaveBeenCalled();
});

test.each(['tree_leaf_edit', 'tree_full_edit'])('Set mode correctly from fieldTypeOptions', (mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: mode,
            }}
            formInspector={formInspector}
            value="/test/xxx"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe(mode);
});

test.each(['tree_leaf_edit', 'tree_full_edit'])('Set mode correctly from schemaOptions', (mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                // invert to be sure that schemaOptions are used
                defaultMode: (mode === 'tree_leaf_edit' ? 'tree_full_edit' : 'tree_leaf_edit'),
            }}
            formInspector={formInspector}
            schemaOptions={{
                mode: {
                    name: 'mode',
                    value: mode,
                },
            }}
            value="/test/xxx"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe(mode);
});

test('Should fire onFinish callback without argument when ResourceLocatorComponent is blurred', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    resourceLocator.update();
    resourceLocator.find(ResourceLocatorComponent).prop('onBlur')('Test');

    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should automatically request new URL when part field is finished on add form', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
        '/admin/api/resource-locators',
        {
            locale: 'en',
            resourceKey: 'tests',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
        }
    );

    return resourceLocatorPromise.then(() => {
        expect(changeSpy).toHaveBeenCalledWith('/test');
    });
});

test('Should request URL with parameters from FormInspector options, fieldTypeOptions and schemaOptions', () => {
    const resourceStore = new ResourceStore('test', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
        '/propertyName': 'property-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
                resourceStorePropertiesToRequest: {
                    propertyName: 'requestParamKey',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{
                route_schema: {name: 'route_schema', value: '/events/{implode("-", object)}'},
            }}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
        '/admin/api/resource-locators',
        {
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            routeSchema: '/events/{implode("-", object)}',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    return resourceLocatorPromise.then(() => {
        expect(changeSpy).toHaveBeenCalledWith('/test');
    });
});

test('Should not request new URL when part field is finished on edit form', () => {
    const resourceStore = new ResourceStore('test', 5, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
            value="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Should not request new URL when part field is finished if all parts are empty', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    resourceStore.data = {
        '/title': '',
        '/subtitle': undefined,
    };

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Should not request new URL when part field is finished if input was already changed manually', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    resourceLocator.find(ResourceLocatorComponent).props().onChange('manual-change');

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Should not request new URL when field without the "sulu.rlp.part" tag is finished', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'other-tag'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Should not request new URL when field without any tags has finished editing', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({});

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Should enable refresh button when value of part field changes on edit form', () => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceStore.data['/title'] = 'new-title-value';

    expect(resourceLocator.find('Button').props().disabled).toBeFalsy();
});

test('Should enable refresh button when input is changed manually on edit form', () => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceLocator.find(ResourceLocatorComponent).props().onChange('manual-change');

    expect(resourceLocator.find('Button').props().disabled).toBeFalsy();
});

test('Should not enable refresh button when value of part field changes on add form', () => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceStore.data['/title'] = 'new-title-value';

    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();
});

test('Should enable refresh button when input is changed manually on add form', () => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceLocator.find(ResourceLocatorComponent).props().onChange('manual-change');

    expect(resourceLocator.find('Button').props().disabled).toBeFalsy();
});

test('Should not enable refresh button when value of part field changes if all parts are empty', () => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    resourceLocator.update();
    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceStore.data['/title'] = '';
    resourceStore.data['/subtitle'] = undefined;

    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();

    resourceLocator.find(ResourceLocatorComponent).props().onChange('manual-change');

    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();
});

test('Should request new URL with correct options and disable button when refresh button is clicked', () => {
    const resourceStore = new ResourceStore('test', 5, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
        '/propertyName': 'property-value',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
                resourceStorePropertiesToRequest: {
                    propertyName: 'requestParamKey',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );
    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    resourceLocator.update();

    resourceLocator.find(ResourceLocatorComponent).props().onChange('manual-change');
    expect(resourceLocator.find('Button').props().disabled).toBeFalsy();

    resourceLocator.find('Button').props().onClick();

    expect(resourceLocator.find('Button').props().disabled).toBeTruthy();
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
        '/admin/api/resource-locators',
        {
            resourceId: 5,
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    return resourceLocatorPromise.then(() => {
        expect(changeSpy).toHaveBeenCalledWith('/test');
    });
});
