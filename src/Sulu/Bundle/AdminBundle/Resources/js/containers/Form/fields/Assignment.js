// @flow
import React from 'react';
import AssignmentComponent from '../../Assignment';
import type {FieldTypeProps} from '../../../types';

export default class Assignment extends React.Component<FieldTypeProps<Array<string | number>>> {
    render() {
        const {fieldTypeOptions, formInspector, onChange, value} = this.props;

        if (!formInspector) {
            throw new Error('The assignment field needs a working FormInspector to work properly');
        }

        if (!fieldTypeOptions) {
            throw new Error('The assignment field needs a "resourceKey" and a "adapter" option to work properly');
        }

        if (!fieldTypeOptions.resourceKey) {
            throw new Error('The assignment field needs a "resourceKey" option to work properly');
        }

        if (!fieldTypeOptions.adapter) {
            throw new Error('The assignment field needs a "adapter" option to work properly');
        }

        const {adapter, displayProperties, icon, label, resourceKey, overlayTitle} = fieldTypeOptions;

        return (
            <AssignmentComponent
                adapter={adapter}
                displayProperties={displayProperties}
                icon={icon}
                label={label}
                locale={formInspector.locale}
                onChange={onChange}
                resourceKey={resourceKey}
                overlayTitle={overlayTitle}
                value={value || []}
            />
        );
    }
}
