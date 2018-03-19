// @flow
import React from 'react';
import AssignmentComponent from '../../Assignment';
import type {FieldTypeProps} from '../../../types';

export default class Assignment extends React.Component<FieldTypeProps<Array<string | number>>> {
    render() {
        const {fieldOptions, onChange, locale, value} = this.props;

        if (!fieldOptions) {
            throw new Error('The assignment field needs a "resourceKey" and a "adapter" option to work properly');
        }

        if (!fieldOptions.resourceKey) {
            throw new Error('The assignment field needs a "resourceKey" option to work properly');
        }

        if (!fieldOptions.adapter) {
            throw new Error('The assignment field needs a "adapter" option to work properly');
        }

        const {adapter, displayProperties, icon, label, resourceKey, overlayTitle} = fieldOptions;

        return (
            <AssignmentComponent
                adapter={adapter}
                displayProperties={displayProperties}
                icon={icon}
                label={label}
                locale={locale}
                onChange={onChange}
                resourceKey={resourceKey}
                overlayTitle={overlayTitle}
                value={value || []}
            />
        );
    }
}
