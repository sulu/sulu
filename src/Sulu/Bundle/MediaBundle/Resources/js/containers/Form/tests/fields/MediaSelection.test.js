// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import FormStore from 'sulu-admin-bundle/containers/Form/stores/FormStore';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {observable} from 'mobx';
import MediaSelection from '../../fields/MediaSelection';
import MediaSelectionComponent from '../../../MediaSelection/MediaSelection';

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

test('Pass correct props to MediaSelection component', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
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

    expect(mediaSelection.find(MediaSelectionComponent).props().disabled).toEqual(true);
    expect(mediaSelection.find(MediaSelectionComponent).props().locale.get()).toEqual('en');
    expect(mediaSelection.find(MediaSelectionComponent).props().value).toEqual([55, 66, 77]);
});

test('Should throw an error if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: undefined })
        )
    );

    expect(() => shallow(
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{ids: [55, 66, 77]}}
        />
    )).toThrowError();
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
        <MediaSelection
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{ids: [55, 66, 77]}}
        />
    );

    mediaSelection.find(MediaSelectionComponent).props().onChange([33, 44]);

    expect(changeSpy).toBeCalledWith({ ids: [33, 44] });
    expect(finishSpy).toBeCalled();
});
