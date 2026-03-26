// @flow
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import {observable} from 'mobx';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import fieldRegistry from 'sulu-admin-bundle/containers/Form/registries/fieldRegistry';
import SingleSelect from 'sulu-admin-bundle/containers/Form/fields/SingleSelect';
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

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'en',
}));

jest.mock('../../../ImageMap', () => {
    return jest.fn(() => null);
});

jest.mock('sulu-admin-bundle/containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn().mockReturnValue(() => <div>field type mock</div>),
    getOptions: jest.fn().mockReturnValue({}),
}));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

function createFormInspector(locale: ?string = 'en') {
    return new FormInspector(
        new ResourceFormStore(
            new ResourceStore(
                'test',
                undefined,
                locale !== undefined ? {locale: observable.box(locale)} : {}
            ),
            'test'
        )
    );
}

function renderImageMap(props: Object = {}) {
    return render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={createFormInspector()}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text: {
                            label: 'Text',
                            type: 'text_line',
                        },
                    },
                },
            }}
            {...props}
        />
    );
}

function getLatestImageMapContainerProps() {
    const calls = ((ImageMapContainer: any).mock.calls: any);
    const props = calls[calls.length - 1][0];

    return {
        ...props,
        value: props.value === undefined ? {hotspots: [], imageId: undefined} : props.value,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to ImageMapContainer component', () => {
    renderImageMap({
        disabled: true,
        error: {keyword: 'mandatory', parameters: {}},
        value: {imageId: 33, hotspots: []},
    });

    expect(getLatestImageMapContainerProps().disabled).toEqual(true);
    expect(getLatestImageMapContainerProps().valid).toEqual(false);
    expect(getLatestImageMapContainerProps().locale.get()).toEqual('en');
    expect(getLatestImageMapContainerProps().types).toEqual({'default': 'Default'});
    expect(getLatestImageMapContainerProps().value).toEqual({imageId: 33, hotspots: []});
});

test('Pass correct default value to ImageMapContainer', () => {
    renderImageMap({
        value: undefined,
    });

    expect(getLatestImageMapContainerProps().value).toEqual({imageId: undefined, hotspots: []});
});

test('Pass content-locale of user to SingleMediaSelection if locale is not present in form-inspector', () => {
    renderImageMap({
        formInspector: createFormInspector(undefined),
        value: {imageId: 44, hotspots: []},
    });

    expect(getLatestImageMapContainerProps().locale.get()).toEqual('en');
});

test('Should call onChange and onFinish if the value changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

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

    renderImageMap({
        data,
        dataPath: 'imageMapProperty',
        onChange: changeSpy,
        onFinish: finishSpy,
        value,
    });

    const imageMapContainerProps = getLatestImageMapContainerProps();
    imageMapContainerProps.onChange({imageId: 44, hotspots: []});
    imageMapContainerProps.onFinish();

    expect(changeSpy).toBeCalledWith({imageId: 44, hotspots: []});
    expect(finishSpy).toBeCalled();

    const hotspotFormElement = imageMapContainerProps.renderHotspotForm(value.hotspots[0], 'default', 0);
    expect(hotspotFormElement.props.data).toEqual(data);
    expect(hotspotFormElement.props.value).toEqual(value.hotspots[0]);
    expect(jsonpointer.get(hotspotFormElement.props.data, '/' + hotspotFormElement.props.dataPath))
        .toEqual(hotspotFormElement.props.value);
});

test('Should set correct default values for multiple single_select in form', async() => {
    const changeSpy = jest.fn();

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

    renderImageMap({
        onChange: changeSpy,
        types,
        value: {
            hotspots: [
                {
                    hotspot: {type: 'point'},
                    type: 'default',
                },
            ],
            imageId: 55,
        },
    });

    const imageMapContainerProps = getLatestImageMapContainerProps();
    render(
        imageMapContainerProps.renderHotspotForm(
            {hotspot: {type: 'point'}, type: 'default'},
            'default',
            0
        )
    );

    await waitFor(() => expect(changeSpy).toBeCalledWith(
        {
            hotspots: [{
                hotspot: {type: 'point'},
                position_left: 'left',
                position_right: 'right',
                type: 'default',
            }],
            imageId: 55,
        }
    ));
});
