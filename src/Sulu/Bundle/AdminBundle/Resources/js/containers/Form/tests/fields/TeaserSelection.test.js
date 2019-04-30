// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TeaserSelection from '../../fields/TeaserSelection';
import TeaserSelectionComponent from '../../../../containers/TeaserSelection';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.locale = observableOptions.locale;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

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

    expect(field.find(TeaserSelectionComponent).prop('locale').get()).toEqual('en');
    expect(field.find(TeaserSelectionComponent).prop('onChange')).toBe(changeSpy);
    expect(field.find(TeaserSelectionComponent).prop('value')).toBe(value);
});
