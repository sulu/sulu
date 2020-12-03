// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore, userStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {Router} from 'sulu-admin-bundle/services';
import TeaserSelection from '../../fields/TeaserSelection';
import TeaserSelectionComponent from '../../../../containers/TeaserSelection';
import teaserProviderRegistry from '../../../../containers/TeaserSelection/registries/teaserProviderRegistry';
import TeaserStore from '../../../../containers/TeaserSelection/stores/TeaserStore';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({}));

jest.mock('../../../../containers/TeaserSelection/stores/TeaserStore', () => jest.fn(function() {
    this.add = jest.fn();
    this.findById = jest.fn();
}));

jest.mock('../../../../containers/TeaserSelection/registries/teaserProviderRegistry', () => ({
    get: jest.fn(),
    keys: [],
}));

test('Pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = {
        presentAs: undefined,
        items: [],
    };

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore(
                'test',
                undefined,
                {locale: observable.box('en')}
            ),
            'snippets'
        )
    );

    const field = shallow(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            value={value}
        />
    );

    expect(field.find(TeaserSelectionComponent).prop('disabled')).toEqual(false);
    expect(field.find(TeaserSelectionComponent).prop('locale').get()).toEqual('en');
    expect(field.find(TeaserSelectionComponent).prop('presentations')).toBe(undefined);
    expect(field.find(TeaserSelectionComponent).prop('value')).toBe(value);
});

test('Pass disabled value from props to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const field = shallow(<TeaserSelection {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />);

    expect(field.find(TeaserSelectionComponent).prop('disabled')).toEqual(true);
});

test('Pass locale from userStore when form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const field = shallow(<TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(field.find(TeaserSelectionComponent).prop('locale').get()).toEqual('de');
});

test('Pass presentations prop correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: [
                {name: 'test-1', title: 'Test 1'},
                {name: 'test-2', title: 'Test 2'},
            ],
        },
    };

    const field = shallow(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(field.find(TeaserSelectionComponent).prop('presentations')).toEqual([
        {label: 'Test 1', value: 'test-1'},
        {label: 'Test 2', value: 'test-2'},
    ]);
});

test('Navigate to item when item is clicked', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    teaserProviderRegistry.get.mockReturnValue({
        resultToView: {'attributes/webspaceKey': 'webspace', id: 'id'},
        title: 'Pages',
        view: 'sulu_page.page_edit_form',
    });

    const value = {
        presentAs: '',
        items: [
            {
                id: 5,
                type: 'pages',
            },
            {
                id: 2,
                type: 'pages',
            },
        ],
    };

    const router = new Router();

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn((type, id) => {
            if (id === 5) {
                return {attributes: {webspaceKey: 'sulu_io'}, title: 'Test 1'};
            }

            if (id === 2) {
                return {attributes: {webspaceKey: 'sulu_blog'}, title: 'Test 2'};
            }
        });
    });

    const field = mount(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            value={value}
        />
    );

    field.find('MultiItemSelection .content.clickable').at(0).simulate('click');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {id: 5, webspace: 'sulu_io'});
    field.find('MultiItemSelection .content.clickable').at(1).simulate('click');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_page.page_edit_form', {id: 2, webspace: 'sulu_blog'});
});

test('Throw error if present_as schemaOption is from wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const schemaOptions = {
        present_as: {
            name: 'present_as',
            value: 'test',
        },
    };

    expect(
        () => shallow(
            <TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />
        )
    ).toThrow(/present_as/);
});

test('Should call onChange and onFinish callback when TeaserSelection container fires onChange callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    // $FlowFixMe
    userStore.contentLocale = 'de';

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const field = shallow(
        <TeaserSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    field.find(TeaserSelectionComponent).prop('onChange')({
        presentAs: undefined,
        items: [],
    });

    expect(changeSpy).toBeCalledWith({
        presentAs: undefined,
        items: [],
    });
    expect(finishSpy).toBeCalledWith();
});
