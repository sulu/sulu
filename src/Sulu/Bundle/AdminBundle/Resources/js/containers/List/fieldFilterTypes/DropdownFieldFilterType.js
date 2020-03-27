// @flow
import React from 'react';
import {computed} from 'mobx';
import MultiSelect from '../../../components/MultiSelect';
import {translate} from '../../../utils/Translator';
import AbstractFieldFilterType from './AbstractFieldFilterType';

class DropdownFieldFilterType extends AbstractFieldFilterType<?Array<string>> {
    @computed get options(): Object {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "DropdownFieldFilterType" needs some parameters to work!');
        }

        const {options} = parameters;

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
            <MultiSelect onChange={this.handleChange} values={value || []}>
                {Object.keys(this.options).map((optionKey) => (
                    <MultiSelect.Option
                        key={optionKey}
                        value={optionKey}
                    >
                        {translate(this.options[optionKey])}
                    </MultiSelect.Option>
                ))}
            </MultiSelect>
        );
    }

    getValueNode(values: ?Array<string>) {
        if (!values) {
            return Promise.resolve(null);
        }

        return Promise.resolve(values.map((value) => translate(this.options[value])).join(', '));
    }
}

export default DropdownFieldFilterType;
