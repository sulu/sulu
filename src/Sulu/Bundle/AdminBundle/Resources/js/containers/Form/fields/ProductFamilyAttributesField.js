// @flow
import React from 'react';
import {observable} from 'mobx';
import ProductFamilyAttributes from '../../Product/ProductFamilyAttributes';
import userStore from '../../../stores/userStore';
import type {FieldTypeProps} from '../../../types';
import type {Entry} from '../../Product/ProductFamilyAttributes/ProductFamilyAttributes';

type Props = FieldTypeProps<?Array<Entry>>;

export default class ProductFamilyAttributesField extends React.Component<Props> {
    handleChange = (value: Array<Entry>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, value} = this.props;

        // formInspector.locale is nullable, the container always needs one; fall back like the other fields do.
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        return (
            <ProductFamilyAttributes
                disabled={!!disabled}
                locale={locale}
                onChange={this.handleChange}
                value={value}
            />
        );
    }
}
