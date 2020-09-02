// @flow
import React from 'react';
import {observable, toJS} from 'mobx';
import debounce from 'debounce';
import userStore from 'sulu-admin-bundle/stores/userStore';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {BlockError} from 'sulu-admin-bundle/containers/Form/types';
import FieldRenderer from 'sulu-admin-bundle/containers/FieldBlocks/FieldRenderer';
import jsonpointer from 'json-pointer';
import ImageMapContainer from '../../ImageMap';
import type {Value as ImageMapValue, RenderHotspotFormCallback} from '../../ImageMap/types';

const MISSING_TYPE_ERROR_MESSAGE = 'The "image_map" field type needs at least one type to be configured!';

export default class ImageMap extends React.Component<FieldTypeProps<ImageMapValue>> {
    handleFinish = debounce(() => {
        const {onFinish} = this.props;

        onFinish();
    }, 1000);

    handleChange = (value: ImageMapValue) => {
        const {onChange} = this.props;

        onChange(value);
        this.handleFinish();
    };

    get defaultValue(): ImageMapValue {
        return {
            imageId: undefined,
            hotspots: [],
        };
    }

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
                'The default type should exist in image_map "' + schemaPath + '".'
            );
        }

        return types[defaultType];
    };

    handleHotspotFormChange = (index: number, name: string, value: Object) => {
        const {onChange, value: oldValues} = this.props;

        if (!oldValues) {
            return;
        }

        const newValues = toJS(oldValues);
        jsonpointer.set(newValues.hotspots[index], '/' + name, value);

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
            error,
            disabled,
            formInspector,
            types,
            value,
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
                formTypes={formTypes}
                locale={locale}
                onChange={this.handleChange}
                renderHotspotForm={this.renderHotspotForm}
                valid={!error}
                value={value || this.defaultValue}
            />
        );
    }
}
