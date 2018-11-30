// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import FormStore from 'sulu-admin-bundle/containers/Form/stores/FormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {observable} from 'mobx';
import SingleMediaSelectionComponent from '../../../SingleMediaSelection';
import SingleMediaSelection from '../../fields/SingleMediaSelection';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/FormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/UserStore', () => ({
    contentLocale: 'userContentLocale',
}));

test('Pass correct props to SingleMediaSelection component', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            error={{keyword: 'mandatory', parameters: {}}}
            formInspector={formInspector}
            value={{id: 33}}
        />
    );

    expect(mediaSelection.find(SingleMediaSelectionComponent).props().disabled).toEqual(true);
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().valid).toEqual(false);
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().locale.get()).toEqual('en');
    expect(mediaSelection.find(SingleMediaSelectionComponent).props().value).toEqual({id: 33});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {})
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{id: 33}}
        />
    );

    expect(mediaSelection.find(SingleMediaSelectionComponent).props().locale.get()).toEqual('userContentLocale');
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
        )
    );

    const mediaSelection = shallow(
        <SingleMediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{id: 55}}
        />
    );

    mediaSelection.find(SingleMediaSelectionComponent).props().onChange({id: 44});

    expect(changeSpy).toBeCalledWith({id: 44});
    expect(finishSpy).toBeCalled();
});
