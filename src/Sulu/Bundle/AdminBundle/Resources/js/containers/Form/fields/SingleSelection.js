// @flow
import React from 'react';
import {computed} from 'mobx';
import type {FieldTypeProps} from '../../../types';
import SingleAutoComplete from '../../../containers/SingleAutoComplete';
import SingleSelectionComponent from '../../../containers/SingleSelection';
import {translate} from '../../../utils/Translator';

export default class SingleSelection extends React.Component<FieldTypeProps<?Object | string | number>>
{
    handleChange = (value: ?Object | string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    @computed get type() {
        return this.props.fieldTypeOptions.default_type;
    }

    render() {
        if (this.type === 'overlay') {
            return this.renderOverlay();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }
    }

    renderOverlay() {
        const {
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    overlay: {
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
            // TODO implement id value support for overlay type
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
                searchProperties={searchProperties}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }
}
