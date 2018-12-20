// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import FormStore from 'sulu-admin-bundle/containers/Form/stores/FormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {observable} from 'mobx';
import MediaSelection from '../../fields/MediaSelection';
import MultiMediaSelection from '../../../MultiMediaSelection';

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

test('Pass correct props to MultiMediaSelection component', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{ids: [55, 66, 77]}}
        />
    );

    expect(mediaSelection.find(MultiMediaSelection).props().disabled).toEqual(true);
    expect(mediaSelection.find(MultiMediaSelection).props().locale.get()).toEqual('en');
    expect(mediaSelection.find(MultiMediaSelection).props().value).toEqual({ids: [55, 66, 77]});
});

test('Pass content-locale of user to MultiMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <MediaSelection
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={{ids: [55, 66, 77]}}
        />
    );

    expect(mediaSelection.find(MultiMediaSelection).props().locale.get()).toEqual('userContentLocale');
});

test('Should call onChange and onFinish if the selection changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const mediaSelection = shallow(
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{ids: [55, 66, 77]}}
        />
    );

    mediaSelection.find(MultiMediaSelection).props().onChange({ids: [33, 44]});

    expect(changeSpy).toBeCalledWith({ids: [33, 44]});
    expect(finishSpy).toBeCalled();
});
