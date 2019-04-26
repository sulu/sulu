// @flow
import React from 'react';
import TeaserSelectionComponent from '../../TeaserSelection';
import type {FieldTypeProps} from '../../../types';

export default class TeaserSelection extends React.Component<FieldTypeProps<void>> {
    handleChange = () => {
        // TODO implement
    };

    render() {
        const {value} = this.props; // TODO remove default value

        return (
            <TeaserSelectionComponent onChange={this.handleChange} value={value || {displayOption: '', items: []}} />
        );
    }
}
