// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {ResourceStore, userStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import TeaserSelection from '../../fields/TeaserSelection';
import TeaserSelectionComponent from '../../../../containers/TeaserSelection';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/stores/UserStore', () => ({}));

test('Pass props correctly to component', () => {
    const changeSpy = jest.fn();
    const value = {
        displayOption: undefined,
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
    expect(field.find(TeaserSelectionComponent).prop('onChange')).toBe(changeSpy);
    expect(field.find(TeaserSelectionComponent).prop('presentations')).toBe(undefined);
    expect(field.find(TeaserSelectionComponent).prop('value')).toBe(value);
});

test('Pass disabled value from props to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    userStore.contentLocale = 'de';

    const field = shallow(<TeaserSelection {...fieldTypeDefaultProps} disabled={true} formInspector={formInspector} />);

    expect(field.find(TeaserSelectionComponent).prop('disabled')).toEqual(true);
});

test('Pass locale from userStore when form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    userStore.contentLocale = 'de';

    const field = shallow(<TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(field.find(TeaserSelectionComponent).prop('locale').get()).toEqual('de');
});

test('Pass presentations prop correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    userStore.contentLocale = 'de';

    const schemaOptions = {
        present_as: {
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

test('Throw error if present_as schemaOption is from wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    userStore.contentLocale = 'de';

    const schemaOptions = {
        present_as: {
            value: 'test',
        },
    };

    expect(
        () => shallow(
            <TeaserSelection {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />
        )
    ).toThrow(/present_as/);
});
