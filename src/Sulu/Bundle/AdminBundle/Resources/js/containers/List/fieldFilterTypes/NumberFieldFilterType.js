// @flow
import React from 'react';
import {computed} from 'mobx';
import Input from '../../../components/Input';
import SingleSelect from '../../../components/SingleSelect';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import numberFieldFilterTypeStyles from './numberFieldFilterType.scss';

const operatorMapping = {
    lt: '<',
    eq: '=',
    gt: '>',
};

function getOperatorFromValue(value: ?{[string]: ?number}) {
    const valueKeys = value ? Object.keys(value) : {};

    if (valueKeys.length > 1) {
        throw new Error('The "NumberFilterFieldType" only accepts an array with exactly one key!');
    }

    return valueKeys[0];
}

function getNumberFromValue(value: ?{[string]: ?number}) {
    if (!value) {
        return undefined;
    }

    return value[Object.keys(value)[0]];
}

class NumberFieldFilterType extends AbstractFieldFilterType<?{[string]: ?number}> {
    constructor(
        onChange: (value: ?{[string]: ?number}) => void,
        parameters: ?{[string]: mixed},
        value: ?{[string]: ?number}
    ) {
        super(onChange, parameters, value);

        if (value === undefined) {
            onChange({eq: undefined});
        }
    }

    @computed get operator() {
        return getOperatorFromValue(this.value);
    }

    @computed get number() {
        return getNumberFromValue(this.value);
    }

    handleOperatorChange = (operatorValue: ?string) => {
        if (!operatorValue) {
            throw new Error('The operator cannot be changed to undefined! This should not happen and is likely a bug.');
        }

        const {onChange} = this;
        onChange({[operatorValue]: this.number});
    };

    handleInputChange = (inputValue: ?string) => {
        const {onChange} = this;
        onChange({[this.operator]: inputValue});
    };

    getFormNode() {
        return (
            <div className={numberFieldFilterTypeStyles.numberFieldFilterType}>
                <SingleSelect onChange={this.handleOperatorChange} value={this.operator}>
                    <SingleSelect.Option value="lt">{operatorMapping.lt}</SingleSelect.Option>
                    <SingleSelect.Option value="eq">{operatorMapping.eq}</SingleSelect.Option>
                    <SingleSelect.Option value="gt">{operatorMapping.gt}</SingleSelect.Option>
                </SingleSelect>
                <Input
                    onChange={this.handleInputChange}
                    type="number"
                    value={this.number}
                />
            </div>
        );
    }

    getValueNode(value: ?{[string]: ?number}) {
        return Promise.resolve(
            (operatorMapping[getOperatorFromValue(value)] || '') + ' ' + (getNumberFromValue(value) || '')
        );
    }
}

export default NumberFieldFilterType;
