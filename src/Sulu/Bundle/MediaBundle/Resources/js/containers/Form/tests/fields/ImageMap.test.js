// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {
    fieldTypeDefaultProps,
    mockResizeObserver,
} from 'sulu-admin-bundle/utils/TestHelper';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import fieldRegistry from 'sulu-admin-bundle/containers/Form/registries/fieldRegistry';
import SingleSelect from 'sulu-admin-bundle/containers/Form/fields/SingleSelect';
import jsonpointer from 'json-pointer';
import ImageMap from '../../fields/ImageMap';

let mockSingleSelectionStoreInstances: Array<Object> = [];

function mockFieldType(props) {
    const dataPointer = props.dataPath.startsWith('/') ? props.dataPath : '/' + props.dataPath;
    const valueAtPath = props.data && jsonpointer.has(props.data, dataPointer)
        ? jsonpointer.get(props.data, dataPointer)
        : undefined;

    return (
        <div
            data-data-path={props.dataPath}
            data-testid="field-type-mock"
            data-value-at-path={JSON.stringify(valueAtPath)}
        >
            {JSON.stringify(props.value)}
        </div>
    );
}

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn(function() {
    this.loadItem = jest.fn();
    this.loading = false;
    mockSingleSelectionStoreInstances.push(this);
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
    get: jest.fn().mockReturnValue(mockFieldType),
    getOptions: jest.fn().mockReturnValue({}),
}));

mockResizeObserver();

beforeEach(() => {
    fieldRegistry.get.mockReturnValue(mockFieldType);
    mockSingleSelectionStoreInstances = [];
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
                },
            },
        },
    };

    render(
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

    const SingleSelectionStoreMock = (SingleSelectionStore: any);
    const storeCall = SingleSelectionStoreMock.mock.calls[SingleSelectionStoreMock.mock.calls.length - 1];
    const selectionStore = mockSingleSelectionStoreInstances[mockSingleSelectionStoreInstances.length - 1];
    expect(storeCall[0]).toEqual('media');
    expect(storeCall[1]).toBeUndefined();
    expect(storeCall[2].get()).toEqual('en');
    expect(selectionStore.loadItem).toHaveBeenCalledWith(33);
    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'su-plus-circle'})).toBeDisabled();
    expect(screen.getByText('sulu_media.select_media_singular').closest('.error')).not.toBeNull();
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

    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={undefined}
        />
    );

    expect(screen.getByText('sulu_media.select_media_singular')).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'su-plus-circle'})).not.toBeInTheDocument();
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

    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={{imageId: 44, hotspots: []}}
        />
    );

    const SingleSelectionStoreMock = (SingleSelectionStore: any);
    const storeCall = SingleSelectionStoreMock.mock.calls[SingleSelectionStoreMock.mock.calls.length - 1];
    expect(storeCall[2].get()).toEqual('en');
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
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={types}
            value={value}
        />
    );

    const field = screen.getByTestId('field-type-mock');
    expect(field).toHaveAttribute('data-data-path', 'imageMapProperty/hotspots/0/text');
    expect(field).toHaveAttribute('data-value-at-path', '"text-value-123"');
    expect(field).toHaveTextContent('"text-value-123"');
});

test('Should call onChange and onFinish if the value changes', async() => {
    const user = userEvent.setup();
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

    render(
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

    await user.click(screen.getByRole('button', {name: 'su-plus-circle'}));

    expect(changeSpy).toHaveBeenCalledWith({
        imageId: 55,
        hotspots: [{hotspot: {type: 'point'}, type: 'default'}],
    });
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

    render(
        <ImageMap
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={types}
            value={{imageId: 55, hotspots: []}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-plus-circle'}));

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
