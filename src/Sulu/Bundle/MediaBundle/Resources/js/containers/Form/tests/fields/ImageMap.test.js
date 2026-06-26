// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {
    fieldTypeDefaultProps,
    findElementByType,
    mockResizeObserver,
    renderWithRef,
} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import fieldRegistry from 'sulu-admin-bundle/containers/Form/registries/fieldRegistry';
import SingleSelect from 'sulu-admin-bundle/containers/Form/fields/SingleSelect';
import {Renderer} from 'sulu-admin-bundle/containers/Form';
import jsonpointer from 'json-pointer';
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

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'en',
}));

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn().mockReturnValue(() => <div>field type mock</div>),
    getOptions: jest.fn().mockReturnValue({}),
}));

mockResizeObserver();

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
                },
            },
        },
    };

    const {instance: imageMap} = renderWithRef(
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
    const imageMapContainerProps = findElementByType(imageMap.render(), ImageMapContainer).props;

    expect(imageMapContainerProps.disabled).toEqual(true);
    expect(imageMapContainerProps.valid).toEqual(false);
    expect(imageMapContainerProps.locale.get()).toEqual('en');
    expect(imageMapContainerProps.types).toEqual({'default': 'Default'});
    expect(imageMapContainerProps.value).toEqual({imageId: 33, hotspots: []});
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
                },
            },
        },
    };

    const {instance: imageMap} = renderWithRef(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={undefined}
        />
    );

    expect(findElementByType(imageMap.render(), ImageMapContainer).props.value)
        .toEqual({imageId: undefined, hotspots: []});
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
                },
            },
        },
    };

    const {instance: imageMap} = renderWithRef(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={{imageId: 44, hotspots: []}}
        />
    );

    expect(findElementByType(imageMap.render(), ImageMapContainer).props.locale.get()).toEqual('en');
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
                },
            },
        },
    };

    const value = {
        imageId: 55,
        hotspots: [
            {'hotspot': {'type': 'point'}, 'type': 'default', 'text': 'text-value-123'},
        ],
    };

    const data = {
        imageMapProperty: value,
        otherProperty: 'other-value',
    };

    const {instance: imageMap} = renderWithRef(
        <ImageMap
            {...fieldTypeDefaultProps}
            data={data}
            dataPath="imageMapProperty"
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={types}
            value={value}
        />
    );

    const fieldRenderer = findElementByType(imageMap.render(), ImageMapContainer)
        .props.renderHotspotForm(value.hotspots[0], 'default', 0);
    const {instance: renderedField} = renderWithRef(fieldRenderer);

    // check if data path that is passed to field leads to correct value for field
    const rendererProps = findElementByType(renderedField.render(), Renderer).props;
    const fieldData = rendererProps.data;
    const fieldDataPath = rendererProps.dataPath;
    const fieldValue = rendererProps.value;
    expect(jsonpointer.get(fieldData, '/' + fieldDataPath)).toEqual(fieldValue);
});

test('Should pass correct data to Renderer component', () => {
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
                },
            },
        },
    };

    const {instance: imageMap} = renderWithRef(
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

    findElementByType(imageMap.render(), ImageMapContainer).props.onChange({imageId: 44, hotspots: []});
    findElementByType(imageMap.render(), ImageMapContainer).props.onFinish();

    expect(changeSpy).toHaveBeenCalledWith({imageId: 44, hotspots: []});
    expect(finishSpy).toHaveBeenCalled();
});

test('Should set correct default values for multiple single_select in form', async() => {
    const user = userEvent.setup();
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

    const {container} = renderWithRef(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={types}
            value={{imageId: 55, hotspots: []}}
        />
    );

    await user.click(container.querySelectorAll('button')[1]);

    expect(changeSpy).toHaveBeenCalledWith(
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
