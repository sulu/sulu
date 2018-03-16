// @flow
import React from 'react';
import AssignmentComponent from '../../Assignment';
import type {FieldTypeProps} from '../../../types';

export default class Assignment extends React.Component<FieldTypeProps<Array<string | number>>> {
    render() {
        const {fieldOptions, onChange, locale, value} = this.props;

        if (!fieldOptions || !fieldOptions.resourceKey) {
            throw new Error('The assignment field needs a "resourceKey" option to work properly');
        }

        const {displayProperties, icon, label, resourceKey, overlayTitle} = fieldOptions;

        return (
            <AssignmentComponent
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
