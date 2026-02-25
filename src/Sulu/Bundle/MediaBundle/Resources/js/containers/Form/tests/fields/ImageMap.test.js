// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import ImageMap from '../../fields/ImageMap';

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
jest.mock('../../../ImageMap', () => jest.fn(() => null));

const ImageMapContainerMock: any = jest.requireMock('../../../ImageMap');

const createFormInspector = (locale: ?string) => new FormInspector(
    new ResourceFormStore(
        new ResourceStore('test', undefined, locale ? {locale: observable.box(locale)} : {}),
        'test'
    )
);

const createTypes = () => ({
    default: {
        title: 'Default',
        form: {
            text: {
                label: 'Text',
                type: 'text_line',
            },
        },
    },
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to ImageMap container', () => {
    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            disabled={true}
            error={{keyword: 'mandatory', parameters: {}}}
            formInspector={createFormInspector('en')}
            types={createTypes()}
            value={{imageId: 33, hotspots: []}}
        />
    );

    expect(getLatestMockProps(ImageMapContainerMock).disabled).toEqual(true);
    expect(getLatestMockProps(ImageMapContainerMock).valid).toEqual(false);
    expect(getLatestMockProps(ImageMapContainerMock).locale.get()).toEqual('en');
    expect(getLatestMockProps(ImageMapContainerMock).types).toEqual({'default': 'Default'});
    expect(getLatestMockProps(ImageMapContainerMock).value).toEqual({imageId: 33, hotspots: []});
});

test('Pass correct default value to ImageMap container', () => {
    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={createFormInspector(undefined)}
            types={createTypes()}
            value={undefined}
        />
    );

    expect(getLatestMockProps(ImageMapContainerMock).value).toEqual(undefined);
});

test('Pass content-locale of user to ImageMap container if locale is not present in form-inspector', () => {
    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={createFormInspector(undefined)}
            types={createTypes()}
            value={{imageId: 44, hotspots: []}}
        />
    );

    expect(getLatestMockProps(ImageMapContainerMock).locale.get()).toEqual('en');
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

    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            data={{imageMapProperty: value, otherProperty: 'other-value'}}
            dataPath="imageMapProperty"
            defaultType="default"
            formInspector={createFormInspector('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={createTypes()}
            value={value}
        />
    );

    getLatestMockProps(ImageMapContainerMock).onChange({imageId: 44, hotspots: []});
    getLatestMockProps(ImageMapContainerMock).onFinish();

    expect(changeSpy).toBeCalledWith({imageId: 44, hotspots: []});
    expect(finishSpy).toBeCalled();
});

test('Should pass correct data to rendered hotspot form', () => {
    const changeSpy = jest.fn();
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

    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            data={data}
            dataPath="imageMapProperty"
            defaultType="default"
            formInspector={createFormInspector('en')}
            onChange={changeSpy}
            types={createTypes()}
            value={value}
        />
    );

    const hotspotForm = getLatestMockProps(ImageMapContainerMock).renderHotspotForm(value.hotspots[0], 'default', 0);
    expect(hotspotForm.props.data).toEqual(data);
    expect(hotspotForm.props.dataPath).toEqual('imageMapProperty/hotspots/0');
    expect(hotspotForm.props.value).toEqual(value.hotspots[0]);

    hotspotForm.props.onChange(0, 'text', 'changed');
    expect(changeSpy).toBeCalledWith({
        imageId: 55,
        hotspots: [
            {'hotspot': {'type': 'point'}, 'type': 'default', 'text': 'changed'},
        ],
    });
});

test('Should pass single_select schema defaults through hotspot form renderer', () => {
    const types = {
        default: {
            title: 'Default',
            form: {
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
                                {name: 'left', title: 'Left'},
                                {name: 'center', title: 'Center'},
                                {name: 'right', title: 'Right'},
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
                                {name: 'left', title: 'Left'},
                                {name: 'center', title: 'Center'},
                                {name: 'right', title: 'Right'},
                            ],
                        },
                    },
                },
            },
        },
    };

    const hotspot = {'hotspot': {'type': 'point'}, 'type': 'default'};
    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={createFormInspector('en')}
            onChange={jest.fn()}
            types={types}
            value={{imageId: 55, hotspots: [hotspot]}}
        />
    );

    const hotspotForm = getLatestMockProps(ImageMapContainerMock).renderHotspotForm(hotspot, 'default', 0);
    expect(hotspotForm.props.schema).toEqual(types.default.form);
});
