// @flow
import React from 'react';
import {computed} from 'mobx';
import type {FieldTypeProps} from '../../../types';
import SingleAutoComplete from '../../../containers/SingleAutoComplete';
import SingleSelectionComponent from '../../../containers/SingleSelection';
import {translate} from '../../../utils/Translator';

type Props = FieldTypeProps<?Object | string | number>;

export default class SingleSelection extends React.Component<Props>
{
    constructor(props: Props) {
        super(props);

        if (this.type !== 'datagrid_overlay' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "datagrid_overlay" or as "auto_complete", '
                + 'received type was "' + this.type + '"!'
            );
        }
    }

    handleChange = (value: ?Object | string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    @computed get type() {
        return this.props.fieldTypeOptions.default_type;
    }

    render() {
        if (this.type === 'datagrid_overlay') {
            return this.renderDatagridOverlay();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }
    }

    renderDatagridOverlay() {
        const {
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    datagrid_overlay: {
                        adapter,
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
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                emptyText={translate(emptyText)}
                icon={icon}
                locale={formInspector.locale}
                onChange={this.handleChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }

    renderAutoComplete() {
        const {
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
                displayProperty={displayProperty}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                searchProperties={searchProperties}
                value={value}
            />
        );
    }
}
