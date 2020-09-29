// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ImageMap from '../../fields/ImageMap';
import ImageMapContainer from '../../../ImageMap';

jest.mock('debounce', () => jest.fn((value) => value));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'en',
}));

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

test('Pass correct props to SingleMediaSelection component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    const imageMap = shallow(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            error={{keyword: 'mandatory', parameters: {}}}
            formInspector={formInspector}
            types={types}
            value={{imageId: 33, hotspots: []}}
        />
    );

    expect(imageMap.find(ImageMapContainer).props().disabled).toEqual(true);
    expect(imageMap.find(ImageMapContainer).props().valid).toEqual(false);
    expect(imageMap.find(ImageMapContainer).props().locale.get()).toEqual('en');
    expect(imageMap.find(ImageMapContainer).props().types).toEqual({'default': 'Default'});
    expect(imageMap.find(ImageMapContainer).props().value).toEqual({imageId: 33, hotspots: []});
});

test('Pass correct default value to ImageMapContainer', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    const imageMap = shallow(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={undefined}
        />
    );

    expect(imageMap.find(ImageMapContainer).props().value).toEqual({imageId: undefined, hotspots: []});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    const imageMap = shallow(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={{imageId: 44, hotspots: []}}
        />
    );

    expect(imageMap.find(ImageMapContainer).props().locale.get()).toEqual('en');
});

test('Should call onChange and onFinish if the value changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };

    const imageMap = shallow(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={types}
            value={{imageId: 55, hotspots: []}}
        />
    );

    imageMap.find(ImageMapContainer).props().onChange({imageId: 44, hotspots: []});

    expect(changeSpy).toBeCalledWith({imageId: 44, hotspots: []});
    expect(finishSpy).toBeCalled();
});
