// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import fieldRegistry from 'sulu-admin-bundle/containers/Form/registries/fieldRegistry';
import SingleSelect from 'sulu-admin-bundle/containers/Form/fields/SingleSelect';
import ImageMap from '../../fields/ImageMap';
import ImageMapContainer from '../../../ImageMap';

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn(function() {
    this.loadItem = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
    this.isFieldModified = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'en',
}));

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

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
    imageMap.find(ImageMapContainer).props().onFinish();

    expect(changeSpy).toBeCalledWith({imageId: 44, hotspots: []});
    expect(finishSpy).toBeCalled();
});

test('Should set correct default values for multiple single_select in form', () => {
    const changeSpy = jest.fn();

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
                position_center: {
                    label: 'Position Center',
                    type: 'single_select',
                    options: {
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_left: {
                    label: 'Position Left',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'left',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_right: {
                    label: 'Position Right',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'right',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
            },
        },
    };

    fieldRegistry.get.mockReturnValue(SingleSelect);

    const imageMap = mount(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={types}
            value={{imageId: 55, hotspots: []}}
        />
    );

    imageMap.find('Button').at(1).simulate('click');

    expect(changeSpy).toBeCalledWith(
        {
            'hotspots': [{
                'hotspot': {'type': 'point'},
                'position_left': 'left',
                'position_right': 'right',
                'type': 'default',
            }], 'imageId': 55,
        }
    );
});
