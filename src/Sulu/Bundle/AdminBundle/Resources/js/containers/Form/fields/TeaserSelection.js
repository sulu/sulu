// @flow
import React from 'react';
import TeaserSelectionComponent from '../../TeaserSelection';
import type {FieldTypeProps} from '../../../types';
import type {TeaserSelectionValue} from '../../TeaserSelection/types';

export default class TeaserSelection extends React.Component<FieldTypeProps<TeaserSelectionValue>> {
    render() {
        const {formInspector, onChange, value} = this.props;

        return (
            <TeaserSelectionComponent
                locale={formInspector.locale}
                onChange={onChange}
                value={value === null ? undefined : value}
            />
        );
    }
}
