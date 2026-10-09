// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import NumberComponent from '../../../components/Number';
import {translate} from '../../../utils/Translator';
import numberRangeStyles from './numberRange.scss';
import type {FieldTypeProps} from '../types';

type Bound = 'from' | 'to';
type Range = {from: ?number, to: ?number};

/**
 * Edits a range of two numbers, its value `{from, to}`, or null once both are emptied. The "min",
 * "max" and "step" params apply to both inputs, like on the number field type.
 */
@observer
export default class NumberRange extends React.Component<FieldTypeProps<?Range>> {
    getNumberOption(name: string): ?number {
        const option = this.props.schemaOptions[name];

        return option ? parseFloat(option.value) : undefined;
    }

    @computed get min(): ?number {
        return this.getNumberOption('min');
    }

    @computed get max(): ?number {
        return this.getNumberOption('max');
    }

    @computed get step(): ?number {
        return this.getNumberOption('step');
    }

    // An error of the whole value marks both inputs, an error of one bound that input only.
    isValid(bound: Bound): boolean {
        const {error} = this.props;

        if (!error) {
            return true;
        }

        if (typeof error.keyword === 'string') {
            return false;
        }

        // $FlowFixMe: an error collection keyed by bound
        return !error[bound];
    }

    get range(): Range {
        const {value} = this.props;

        return {from: value ? value.from : null, to: value ? value.to : null};
    }

    handleChange(range: Range) {
        this.props.onChange(range.from === null && range.to === null ? null : range);
    }

    handleFromChange = (number: ?number) => {
        this.handleChange({...this.range, from: number ?? null});
    };

    handleToChange = (number: ?number) => {
        this.handleChange({...this.range, to: number ?? null});
    };

    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {dataPath, disabled, value} = this.props;

        return (
            <div className={numberRangeStyles.range}>
                <NumberComponent
                    disabled={!!disabled}
                    id={dataPath}
                    max={this.max}
                    min={this.min}
                    onBlur={this.handleBlur}
                    onChange={this.handleFromChange}
                    placeholder={translate('sulu_admin.from')}
                    step={this.step}
                    valid={this.isValid('from')}
                    value={value ? value.from : undefined}
                />
                <span className={numberRangeStyles.separator}>–</span>
                <NumberComponent
                    disabled={!!disabled}
                    max={this.max}
                    min={this.min}
                    onBlur={this.handleBlur}
                    onChange={this.handleToChange}
                    placeholder={translate('sulu_admin.until')}
                    step={this.step}
                    valid={this.isValid('to')}
                    value={value ? value.to : undefined}
                />
            </div>
        );
    }
}
