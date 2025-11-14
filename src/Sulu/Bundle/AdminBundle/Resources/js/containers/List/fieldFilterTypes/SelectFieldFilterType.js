// @flow
import React from 'react';
import {computed, isArrayLike} from 'mobx';
import Checkbox, {CheckboxGroup} from '../../../components/Checkbox';
import {translate} from '../../../utils/Translator';
import AbstractFieldFilterType from './AbstractFieldFilterType';

class SelectFieldFilterType extends AbstractFieldFilterType<?Array<string>> {
    @computed get parameterOptions(): Object {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "SelectFieldFilterType" needs some parameters to work!');
        }

        let {options} = parameters;

        // Handle both array and object formats from backend
        // Backend may send array for numeric keys: ["value0", "value1", "value2"]
        // Convert to object: {"0": "value0", "1": "value1", "2": "value2"}
        if (isArrayLike(options)) {
            const optionsObject = {};
            // $FlowFixMe: isArrayLike ensures options is array-like and has forEach
            options.forEach((value, index) => {
                optionsObject[String(index)] = value;
            });
            options = optionsObject;
        }

        if (typeof options !== 'object' || options === null) {
            throw new Error('The "options" parameter must be an object!');
        }

        return options;
    }

    handleChange = (values: Array<string>) => {
        this.onChange(values.length > 0 ? values : undefined);
    };

    getFormNode() {
        const {value} = this;

        return (
            <CheckboxGroup onChange={this.handleChange} values={value || []}>
                {Object.keys(this.parameterOptions).map((optionKey) => (
                    <Checkbox
                        key={optionKey}
                        value={optionKey}
                    >
                        {translate(this.parameterOptions[optionKey])}
                    </Checkbox>
                ))}
            </CheckboxGroup>
        );
    }

    getValueNode(values: ?Array<string>) {
        if (!values) {
            return Promise.resolve(null);
        }

        return Promise.resolve(values.map((value) => translate(this.parameterOptions[value])).join(', '));
    }
}

export default SelectFieldFilterType;
