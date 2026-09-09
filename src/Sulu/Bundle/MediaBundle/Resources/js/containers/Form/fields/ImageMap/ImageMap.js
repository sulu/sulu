// @flow
import React from 'react';
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import jsonpointer from 'json-pointer';
import {userStore} from 'sulu-admin-bundle/stores';
import {blockIdGenerator} from 'sulu-admin-bundle/services';
import ImageMapContainer from '../../../ImageMap';
import FieldRenderer from './FieldRenderer';
import type {FieldTypeProps, BlockError} from 'sulu-admin-bundle/types';
import type {Value, RenderHotspotFormCallback} from '../../../ImageMap/types';

const MISSING_TYPE_ERROR_MESSAGE = 'The "image_map" field type needs at least one type to be configured!';

@observer
class ImageMap extends React.Component<FieldTypeProps<Value>> {
    @observable value: Value;
    generatingBlockIds: boolean = false;

    constructor(props: FieldTypeProps<Value>) {
        super(props);

        this.setValue(this.props.value);
    }

    componentDidMount() {
        this.generateMissingBlockIds();
    }

    componentDidUpdate(prevProps: FieldTypeProps<Value>) {
        const {value} = this.props;

        if (!equals(prevProps.value, value)) {
            this.setValue(value);

            this.generateMissingBlockIds();
        }
    }

    get generateBlockIds(): ?boolean {
        const {
            schemaOptions: {
                block_id_generator: {
                    value: blockIdGeneratorEnabled,
                } = {},
            },
        } = this.props;

        if (blockIdGeneratorEnabled !== undefined && typeof blockIdGeneratorEnabled !== 'boolean') {
            throw new Error(
                'The "image_map" field type only accepts booleans as "block_id_generator" schema option!'
            );
        }

        return blockIdGeneratorEnabled;
    }

    // Backfills a generated `_id` on every hotspot that lacks one, regardless of mount state.
    generateMissingBlockIds = async() => {
        const {onChange, types, value} = this.props;

        if (this.generatingBlockIds || !this.generateBlockIds || !types || !value) {
            return;
        }

        this.generatingBlockIds = true;
        try {
            const updatedValue = await blockIdGenerator.ensureBlockIds(toJS(value), types);

            if (updatedValue) {
                this.setValue(updatedValue);
                onChange(updatedValue);
            }
        } finally {
            this.generatingBlockIds = false;
        }
    };

    @action setValue = (value: Object) => {
        this.value = value;
    };

    handleChange = (value: Value) => {
        const {onChange} = this.props;

        this.setValue(value);

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

        this.setValue(newValues);

        onChange(newValues);
    };

    renderHotspotForm: RenderHotspotFormCallback = (value: Object, type: string, index: number) => {
        const {
            data,
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
                data={data}
                dataPath={dataPath + '/hotspots/' + index}
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
                value={value}
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
