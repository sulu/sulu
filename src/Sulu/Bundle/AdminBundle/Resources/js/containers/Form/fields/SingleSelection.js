// @flow
import React from 'react';
import {computed} from 'mobx';
import type {FieldTypeProps} from '../../../types';
import ResourceSingleSelect from '../../../containers/ResourceSingleSelect';
import SingleAutoComplete from '../../../containers/SingleAutoComplete';
import SingleSelectionComponent from '../../../containers/SingleSelection';
import {translate} from '../../../utils/Translator';

type Props = FieldTypeProps<?Object | string | number>;

export default class SingleSelection extends React.Component<Props>
{
    constructor(props: Props) {
        super(props);

        if (this.type !== 'list_overlay' && this.type !== 'single_select' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "list_overlay", "single_select" '
                + 'or as "auto_complete", received type was "' + this.type + '"!'
            );
        }
    }

    handleChange = (value: ?Object | string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    @computed get type() {
        const defaultType = this.props.fieldTypeOptions.default_type;
        if (typeof defaultType !== 'string') {
            throw new Error('The "default_type" field-type option must be a string!');
        }

        const {schemaOptions} = this.props;

        if (!schemaOptions) {
            return defaultType;
        }

        const {
            type: {
                value: type = defaultType,
            } = {},
        } = schemaOptions;

        if (typeof type !== 'string') {
            throw new Error('The "type" schema option must be a string!');
        }

        return type;
    }

    render() {
        if (this.type === 'list_overlay') {
            return this.renderListOverlay();
        }

        if (this.type === 'single_select') {
            return this.renderSingleSelect();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }

        throw new Error('The "' + this.type + '" type does not exist in the SingleSelection field type.');
    }

    renderListOverlay() {
        const {
            disabled,
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    list_overlay: {
                        adapter,
                        list_key: listKey,
                        display_properties: displayProperties,
                        empty_text: emptyText,
                        icon,
                        overlay_title: overlayTitle,
                    },
                },
            },
            value,
        } = this.props;

        if (typeof value === 'object') {
            // TODO implement object value support for overlay type
            throw new Error(
                'The "overlay" type of the SingleSelection field type supports only an ID value until now.'
            );
        }

        return (
            <SingleSelectionComponent
                adapter={adapter}
                disabled={!!disabled}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                emptyText={translate(emptyText)}
                icon={icon}
                listKey={listKey || resourceKey}
                locale={formInspector.locale}
                onChange={this.handleChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }

    renderSingleSelect() {
        const {
            disabled,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    single_select: {
                        display_property: displayProperty,
                        id_property: idProperty,
                        overlay_title: overlayTitle,
                    } = {},
                },
            },
            schemaOptions: {
                editable: {
                    value: editable,
                } = {},
            } = {},
            value,
        } = this.props;

        if (typeof value === 'object') {
            // TODO implement object value support for single_select type
            throw new Error(
                'The "single_select" type of the SingleSelection field type supports only an ID value until now.'
            );
        }

        if (typeof displayProperty !== 'string') {
            throw new Error('The "display_property" field-type option must be a string!');
        }

        if (typeof idProperty !== 'string') {
            throw new Error('The "id_property" field-type option must be a string!');
        }

        return (
            <ResourceSingleSelect
                disabled={!!disabled}
                displayProperty={displayProperty}
                editable={!!editable}
                idProperty={idProperty}
                onChange={this.handleChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }

    renderAutoComplete() {
        const {
            disabled,
            dataPath,
            fieldTypeOptions,
            value,
        } = this.props;

        if (typeof value === 'string' || typeof value === 'number') {
            // TODO implement id value support for auto_complete type
            throw new Error(
                'The "auto_complete" type of the SingleSelection field type supports only an object value until now.'
            );
        }

        if (!fieldTypeOptions.types.auto_complete) {
            throw new Error(
                'The single_selection field needs an "auto_complete" type if rendered as SingleAutoComplete'
            );
        }

        const {
            resource_key: resourceKey,
            types: {
                auto_complete: {
                    display_property: displayProperty,
                    search_properties: searchProperties,
                },
            },
        } = fieldTypeOptions;

        return (
            <SingleAutoComplete
                disabled={!!disabled}
                displayProperty={displayProperty}
                id={dataPath}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                searchProperties={searchProperties}
                value={value}
            />
        );
    }
}
