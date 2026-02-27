// @flow
import React from 'react';
import {computed, makeObservable} from 'mobx';
import Checkbox, {CheckboxGroup} from '../../../components/Checkbox';
import {translate} from '../../../utils/Translator';
import AbstractFieldFilterType from './AbstractFieldFilterType';

function isArrayLike(value: mixed): boolean {
    return !!value
        && typeof value === 'object'
        && typeof value.length === 'number'
        && typeof value.forEach === 'function';
}

class SelectFieldFilterType extends AbstractFieldFilterType<?Array<string>> {
    constructor(...args: Array<any>) {
        super(...args);
        if (typeof makeObservable === 'function') {
            makeObservable(this);
        }
    }

    @computed get parameterOptions(): Object {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "SelectFieldFilterType" needs some parameters to work!');
        }

        let {options} = parameters;

        // Handle both array and object formats from backend
        // Backend may send array for numeric keys: ["value0", "value1", "value2"]
        // Convert to object: {"0": "value0", "1": "value1", "2": "value2"}
        if (Array.isArray(options) || isArrayLike(options)) {
            const normalizedOptions = Array.isArray(options) ? options : Array.from((options: any));
            const optionsObject = {};
            normalizedOptions.forEach((value, index) => {
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
