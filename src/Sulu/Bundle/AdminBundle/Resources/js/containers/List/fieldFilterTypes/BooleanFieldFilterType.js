// @flow
import React from 'react';
import Toggler from '../../../components/Toggler';
import {translate} from '../../../utils/Translator';
import AbstractFieldFilterType from './AbstractFieldFilterType';

class BooleanFieldFilterType extends AbstractFieldFilterType<?boolean> {
    constructor(
        onChange: (value: ?boolean) => void,
        parameters: ?{[string]: mixed},
        value: ?boolean
    ) {
        super(onChange, parameters, value);

        if (value === undefined) {
            onChange(false);
        }
    }

    getFormNode() {
        const {onChange} = this;

        return (
            <Toggler
                checked={this.value || false}
                onChange={onChange}
            />
        );
    }

    getValueNode(value: ?boolean) {
        if (value === undefined) {
            return Promise.resolve(null);
        }

        return Promise.resolve(translate(value ? 'sulu_admin.yes' : 'sulu_admin.no'));
    }
}

export default BooleanFieldFilterType;
