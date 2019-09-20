// @flow
import React from 'react';
import type {FieldTypeProps} from '../../../types';

export default class Location extends React.Component<FieldTypeProps<?string>> {
    render() {
        console.log(this.props);

        return (
            <div>hello world</div>
        );
    }
}
