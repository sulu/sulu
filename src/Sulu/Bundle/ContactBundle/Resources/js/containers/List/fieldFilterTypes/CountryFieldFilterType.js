// @flow
import React from 'react';
import {Checkbox, CheckboxGroup} from 'sulu-admin-bundle/components';
import {AbstractFieldFilterType} from 'sulu-admin-bundle/containers';

class CountryFieldFilterType extends AbstractFieldFilterType<?Array<string>> {
    static countries: {[key: string]: string} = {};

    getFormNode() {
        const {countries} = CountryFieldFilterType;
        const {onChange, value} = this;

        return (
            <CheckboxGroup onChange={onChange} values={value || []}>
                {Object.keys(countries).map((key) => (
                    <Checkbox key={key} value={key}>{countries[key]}</Checkbox>
                ))}
            </CheckboxGroup>
        );
    }

    getValueNode(values: ?Array<string>) {
        const {countries} = CountryFieldFilterType;

        return Promise.resolve(values ? values.map((value) => countries[value]).join(', ') : null);
    }
}

export default CountryFieldFilterType;
