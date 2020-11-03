// @flow
import React from 'react';
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import jsonpointer from 'json-pointer';
import {userStore} from 'sulu-admin-bundle/stores';
import type {FieldTypeProps, BlockError} from 'sulu-admin-bundle/types';
import ImageMapContainer from '../../../ImageMap';
import type {Value, RenderHotspotFormCallback} from '../../../ImageMap/types';
import FieldRenderer from './FieldRenderer';

const MISSING_TYPE_ERROR_MESSAGE = 'The "image_map" field type needs at least one type to be configured!';

@observer
class ImageMap extends React.Component<FieldTypeProps<Value>> {
    @observable value: Value;

    constructor(props: FieldTypeProps<Value>) {
        super(props);
        const {value} = this.props;

        this.updateValue(value);
    }

    componentDidUpdate(prevProps: FieldTypeProps<Value>): * {
        const {value} = this.props;

        if (!equals(prevProps.value, value)){
            this.updateValue(value);
        }
    }

    @action updateValue = (value: Object) => {
        this.value = value;
    };

    handleChange = (value: Value) => {
        const {onChange} = this.props;

        this.updateValue(value);

        onChange(value);
    };

    getHotspotFormSchemaType = (type: ?string) => {
        const {defaultType, schemaPath, types} = this.props;

        if (!type) {
            throw new Error(
                'It is impossible that a hotspot has no formType. This should not happen and is likely a bug.'
            );
        }

        if (!types) {
            throw new Error(MISSING_TYPE_ERROR_MESSAGE);
        }

        if (types[type]) {
            return types[type];
        }

        if (!defaultType) {
            throw new Error(
                'It is impossible that a image_map has no defaultType. This should not happen and is likely a bug.'
            );
        }

        if (!types[defaultType]) {
            throw new Error(
                'The default type should exist in image_map "' + schemaPath + '". ' +
                'This should not happen and is likely a bug.'
            );
        }

        return types[defaultType];
    };

    handleHotspotFormChange = (index: number, name: string, value: Object) => {
        const {onChange} = this.props;
        const oldValues = this.value;

        if (!oldValues) {
            throw new Error(
                'It is impossible that this ImageMap has no value. This should not happen and is likely a bug.'
            );
        }

        const newValues = toJS(oldValues);
        jsonpointer.set(newValues.hotspots[index], '/' + name, value);

        this.updateValue(newValues);

        onChange(newValues);
    };

    renderHotspotForm: RenderHotspotFormCallback = (value: Object, type: string, index: number) => {
        const {
            dataPath,
            error,
            formInspector,
            onFinish,
            onSuccess,
            router,
            schemaPath,
            showAllErrors,
        } = this.props;

        const hotspotFormSchemaType = this.getHotspotFormSchemaType(type);
        const errors = ((toJS(error): any): ?BlockError);

        return (
            <FieldRenderer
                data={value}
                dataPath={dataPath + '/' + index}
                errors={errors && errors.length > index && errors[index] ? errors[index] : undefined}
                formInspector={formInspector}
                index={index}
                onChange={this.handleHotspotFormChange}
                onFieldFinish={onFinish}
                onSuccess={onSuccess}
                router={router}
                schema={hotspotFormSchemaType.form}
                schemaPath={schemaPath + '/types/' + type + '/form'}
                showAllErrors={showAllErrors}
            />
        );
    };

    render() {
        const {
            defaultType,
            disabled,
            error,
            formInspector,
            onFinish,
            types,
        } = this.props;

        const locale = formInspector.locale
            ? formInspector.locale
            : observable.box(userStore.contentLocale);

        if (!defaultType) {
            throw new Error('The "image_map" field type needs a defaultType!');
        }

        if (!types) {
            throw new Error(MISSING_TYPE_ERROR_MESSAGE);
        }

        const formTypes = Object.keys(types).reduce((formTypes, current) => {
            formTypes[current] = types[current].title;
            return formTypes;
        }, {});

        return (
            <ImageMapContainer
                defaultFormType={defaultType}
                disabled={!!disabled}
                locale={locale}
                onChange={this.handleChange}
                onFinish={onFinish}
                renderHotspotForm={this.renderHotspotForm}
                types={formTypes}
                valid={!error}
                value={this.value || undefined}
            />
        );
    }
}

export default ImageMap;
