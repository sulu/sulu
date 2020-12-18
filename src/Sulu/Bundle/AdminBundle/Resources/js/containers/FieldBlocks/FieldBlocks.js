// @flow
import equals from 'fast-deep-equal';
import jsonpointer from 'json-pointer';
import React, {Fragment} from 'react';
import {action, observable, computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import BlockCollection from '../../components/BlockCollection';
import type {BlockEntry} from '../../components/BlockCollection/types';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils/Translator';
import Form, {memoryFormStoreFactory} from '../Form';
import type {BlockError, FieldTypeProps, FormStoreInterface} from '../Form/types';
import blockPreviewTransformerRegistry from './registries/blockPreviewTransformerRegistry';
import FieldRenderer from './FieldRenderer';
import fieldBlocksStyles from './fieldBlocks.scss';

const MISSING_BLOCK_ERROR_MESSAGE = 'The "block" field type needs at least one type to be configured!';
const BLOCK_PREVIEW_TAG = 'sulu.block_preview';
const SETTINGS_KEY = 'settings';
const SETTINGS_PREFIX = '/settings/';
const SETTINGS_TAG = 'sulu.block_setting_icon';

@observer
class FieldBlocks extends React.Component<FieldTypeProps<Array<BlockEntry>>> {
    formRef: ?Form;
    @observable blockSettingsOpen: number | typeof undefined = undefined;
    @observable blockSettingsFormStore: FormStoreInterface;
    @observable value: Object;

    constructor(props: FieldTypeProps<Array<BlockEntry>>) {
        super(props);

        this.setValue(this.props.value);
    }

    @action componentDidMount() {
        if (this.settingsFormKey) {
            const {formInspector} = this.props;

            this.blockSettingsFormStore = memoryFormStoreFactory.createFromFormKey(
                this.settingsFormKey,
                {},
                formInspector.locale,
                undefined,
                formInspector.options
            );
        }
    }

    componentDidUpdate(prevProps: FieldTypeProps<Array<BlockEntry>>) {
        const {defaultType, onChange, types, value} = this.props;
        const {types: oldTypes} = prevProps;

        if (!equals(toJS(prevProps.value), toJS(value))){
            this.setValue(value);
        }

        if (!types || !oldTypes) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        let newValue = toJS(value);

        if (newValue && types !== oldTypes) {
            if (!defaultType) {
                throw new Error(
                    'It is impossible that a block has no defaultType. This should not happen and is likely a bug.'
                );
            }

            // set block to default type if type does not longer exist
            // this could happen for example in a template switch
            newValue = newValue.map((block) => {
                if (!types[block.type]) {
                    return {...block, type: defaultType};
                }

                return block;
            });
        }

        // onChange should only be called when value was changed else it will end in a infinite loop
        if (!equals(toJS(value), newValue)) {
            onChange(newValue);
        }
    }

    componentWillUnmount() {
        this.blockSettingsFormStore?.destroy();
    }

    @computed get settingsFormKey() {
        const {
            schemaOptions: {
                settings_form_key: {
                    value: settingsFormKey,
                } = {},
            },
        } = this.props;

        if (settingsFormKey !== undefined && typeof settingsFormKey !== 'string') {
            throw new Error('The "block" field types only accepts strings as "settings_form_key" schema option!');
        }

        return settingsFormKey;
    }

    @computed get addButtonText() {
        const {
            schemaOptions: {
                add_button_text: {
                    title: addButtonText,
                } = {},
            },
        } = this.props;

        if (addButtonText !== undefined && typeof addButtonText !== 'string') {
            throw new Error('The "block" field types only accepts strings as "add_button_text" schema option!');
        }

        return addButtonText;
    }

    @computed get iconsMapping() {
        const settingsSchema = this.blockSettingsFormStore?.schema;

        if (!settingsSchema) {
            return {};
        }

        const iconMappingReducerCreator = (prefixSchemaKey: string = '') => (iconsMapping, schemaKey) => {
            const pointer = '/' + prefixSchemaKey + schemaKey;

            if (!jsonpointer.has(settingsSchema, pointer)) {
                return iconsMapping;
            }

            const schemaEntry = jsonpointer.get(settingsSchema, pointer);

            if (schemaEntry.items) {
                return Object.keys(schemaEntry.items).reduce(
                    iconMappingReducerCreator(schemaKey + '/items/'),
                    iconsMapping
                );
            }

            const blockSettingsTag = schemaEntry.tags.find((tag) => tag.name === SETTINGS_TAG);

            if (blockSettingsTag) {
                iconsMapping[SETTINGS_PREFIX + schemaKey] = blockSettingsTag.attributes.icon;
            }

            return iconsMapping;
        };

        return Object.keys(settingsSchema).reduce(iconMappingReducerCreator(), {});
    }

    @computed get icons(): Array<Array<string>> {
        if (!this.value) {
            return [];
        }

        return this.value.map((value) => Object.keys(this.iconsMapping).reduce((icons, pointer) => {
            if (jsonpointer.has(value, pointer) && jsonpointer.get(value, pointer)) {
                icons.push(this.iconsMapping[pointer]);
            }

            return icons;
        }, []));
    }

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    @action setValue = (value: Object) => {
        this.value = value;
    };

    handleBlockChange = (index: number, name: string, value: Object) => {
        const {onChange} = this.props;
        const oldValues = this.value;

        if (!oldValues) {
            return;
        }

        const newValues = toJS(oldValues);
        jsonpointer.set(newValues[index], '/' + name, value);

        this.setValue(newValues);

        onChange(newValues);
    };

    handleBlocksChange = (value: Object) => {
        const {onChange} = this.props;

        this.setValue(value);
        onChange(value);
    };

    handleSortEnd = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    getBlockSchemaType = (type: ?string) => {
        const {defaultType, schemaPath, types} = this.props;

        if (!type) {
            throw new Error(
                'It is impossible that a block has no type. This should not happen and is likely a bug.'
            );
        }

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        if (types[type]) {
            return types[type];
        }

        if (!defaultType) {
            throw new Error(
                'It is impossible that a block has no defaultType. This should not happen and is likely a bug.'
            );
        }

        if (!types[defaultType]) {
            throw new Error(
                'The default type should exist in block "' + schemaPath + '".'
            );
        }

        return types[defaultType];
    };

    renderBlockContent = (value: Object, type: string, index: number, expanded: boolean) => {
        return expanded
            ? this.renderExpandedBlockContent(value, type, index)
            : this.renderCollapsedBlockContent(value, type, index);
    };

    renderExpandedBlockContent = (value: Object, type: string, index: number) => {
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

        const blockSchemaType = this.getBlockSchemaType(type);
        const errors = ((toJS(error): any): ?BlockError);

        return (
            <FieldRenderer
                data={data}
                dataPath={dataPath + '/' + index}
                errors={errors && errors.length > index && errors[index] ? errors[index] : undefined}
                formInspector={formInspector}
                index={index}
                onChange={this.handleBlockChange}
                onFieldFinish={onFinish}
                onSuccess={onSuccess}
                router={router}
                schema={blockSchemaType.form}
                schemaPath={schemaPath + '/types/' + type + '/form'}
                showAllErrors={showAllErrors}
                value={value}
            />
        );
    };

    // eslint-disable-next-line no-unused-vars
    renderCollapsedBlockContent = (value: Object, type: string, index: number) => {
        const blockSchemaType = this.getBlockSchemaType(type);
        const blockSchemaTypeForm = this.removeSections(blockSchemaType.form);

        const previewPropertyNames = Object.keys(blockSchemaTypeForm)
            .filter((schemaKey) => {
                const schemaEntryTags = blockSchemaTypeForm[schemaKey].tags;
                return schemaEntryTags &&
                    value[schemaKey] &&
                    schemaEntryTags.some((tag) => tag.name === BLOCK_PREVIEW_TAG);
            })
            .sort((propertyName1, propertyName2) => {
                const propertyTags1 = blockSchemaTypeForm[propertyName1].tags;
                const propertyTags2 = blockSchemaTypeForm[propertyName2].tags;

                if (!propertyTags1 || !propertyTags2) {
                    throw new Error(
                        'All properties without any tag should have been filtered before.'
                        + ' This should not happen and is likely a bug.'
                    );
                }

                const propertyTag1 = propertyTags1.find((tag) => tag.name === BLOCK_PREVIEW_TAG);
                const propertyTag2 = propertyTags2.find((tag) => tag.name === BLOCK_PREVIEW_TAG);

                if (!propertyTag1 || !propertyTag2) {
                    throw new Error(
                        'All properties not having the "sulu.block_preview" tag should have been filtered before.'
                        + ' This should not happen and is likely a bug.'
                    );
                }

                return (propertyTag2.priority || 0) - (propertyTag1.priority || 0);
            });

        if (previewPropertyNames.length === 0) {
            for (const fieldTypeKey of blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority) {
                for (const propertyName of Object.keys(blockSchemaTypeForm)) {
                    if (blockSchemaTypeForm[propertyName].type === fieldTypeKey && value[propertyName]) {
                        previewPropertyNames.push(propertyName);
                        break;
                    }
                }

                if (previewPropertyNames.length >= 3) {
                    break;
                }
            }
        }

        return (
            <Fragment>
                {previewPropertyNames.map((previewPropertyName) =>
                    blockPreviewTransformerRegistry.has(blockSchemaTypeForm[previewPropertyName].type)
                    && value[previewPropertyName]
                    && (
                        <Fragment key={previewPropertyName}>
                            {blockPreviewTransformerRegistry
                                .get(blockSchemaTypeForm[previewPropertyName].type)
                                .transform(value[previewPropertyName], blockSchemaTypeForm[previewPropertyName])
                            }
                        </Fragment>
                    )
                )}
            </Fragment>
        );
    };

    @action handleSettingsClick = (index: number) => {
        if (!this.settingsFormKey || !this.value) {
            return;
        }

        this.blockSettingsOpen = index;
        this.blockSettingsFormStore.setMultiple(this.value[index][SETTINGS_KEY] ?? {});
        this.blockSettingsFormStore.dirty = false;
    };

    @action handleSettingsOverlayClose = () => {
        this.closeSettingsOverlay();
    };

    @action handleSettingsOverlayConfirm = () => {
        this.formRef?.submit();
        this.closeSettingsOverlay();
    };

    @action closeSettingsOverlay = () => {
        this.blockSettingsOpen = undefined;
    };

    handleSettingsSubmit = () => {
        const {onChange} = this.props;
        const oldValues = this.value || [];

        const {blockSettingsFormStore, blockSettingsOpen} = this;

        if (!blockSettingsFormStore || blockSettingsOpen === undefined || !oldValues) {
            return;
        }

        const newValues = [
            ...oldValues.slice(0, blockSettingsOpen),
            {...oldValues[blockSettingsOpen], [SETTINGS_KEY]: blockSettingsFormStore.data},
            ...oldValues.slice(blockSettingsOpen + 1),
        ];

        this.setValue(newValues);
        onChange(newValues);
    };

    removeSections(blockSchemaTypeForm: Object) {
        let filteredForm = {};
        Object.keys(blockSchemaTypeForm).forEach((key) => {
            if (blockSchemaTypeForm[key]['type'] === 'section') {
                filteredForm = {...filteredForm, ...this.removeSections(blockSchemaTypeForm[key]['items'])};
                return false;
            }

            filteredForm[key] = blockSchemaTypeForm[key];
        });

        return filteredForm;
    }

    render() {
        const {defaultType, disabled, maxOccurs, minOccurs, types} = this.props;
        const value = this.value || [];

        if (!defaultType) {
            throw new Error('The "block" field type needs a defaultType!');
        }

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        const blockTypes = Object.keys(types).reduce((blockTypes, current) => {
            blockTypes[current] = types[current].title;
            return blockTypes;
        }, {});

        return (
            <>
                <BlockCollection
                    addButtonText={this.addButtonText}
                    defaultType={defaultType}
                    disabled={!!disabled}
                    icons={this.icons}
                    maxOccurs={maxOccurs}
                    minOccurs={minOccurs}
                    onChange={this.handleBlocksChange}
                    onSettingsClick={this.settingsFormKey ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    renderBlockContent={this.renderBlockContent}
                    types={blockTypes}
                    value={value}
                />
                {this.settingsFormKey &&
                    <Overlay
                        confirmText={translate('sulu_admin.apply')}
                        onClose={this.handleSettingsOverlayClose}
                        onConfirm={this.handleSettingsOverlayConfirm}
                        open={this.blockSettingsOpen !== undefined}
                        size="small"
                        title={translate('sulu_admin.block_settings')}
                    >
                        {!!this.blockSettingsFormStore &&
                            <div className={fieldBlocksStyles.settingsOverlay}>
                                <Form
                                    onSubmit={this.handleSettingsSubmit}
                                    ref={this.setFormRef}
                                    store={this.blockSettingsFormStore}
                                />
                            </div>
                        }
                    </Overlay>
                }
            </>
        );
    }
}

export default FieldBlocks;
