// @flow
import React from 'react';
import TeaserSelectionComponent from '../../TeaserSelection';
import type {FieldTypeProps} from '../../../types';

export default class TeaserSelection extends React.Component<FieldTypeProps<void>> {
    render() {
        return (
            <TeaserSelectionComponent />
        );
    }
}
