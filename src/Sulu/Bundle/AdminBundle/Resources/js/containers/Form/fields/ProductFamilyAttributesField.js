// @flow
import React from 'react';
import {observable} from 'mobx';
import ProductFamilyAttributes from '../../Product/ProductFamilyAttributes';
import userStore from '../../../stores/userStore';
import type {FieldTypeProps} from '../../../types';
import type {Entry} from '../../Product/types';

type Props = FieldTypeProps<?Array<Entry>>;

/**
 * @experimental We can not yet give BC Promise for this new field type in Sulu 3.1.
 */
export default class ProductFamilyAttributesField extends React.Component<Props> {
    handleChange = (value: Array<Entry>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {
            dataPath,
            disabled,
            formInspector,
            router,
            schemaPath,
            schemaOptions: {
                list_key: {
                    value: listKey,
                } = {},
                resource_key: {
                    value: resourceKey,
                } = {},
            } = {},
            value,
        } = this.props;

        if (typeof resourceKey !== 'string') {
            throw new Error('The "resource_key" schema option must be a string!');
        }

        if (typeof listKey !== 'string') {
            throw new Error('The "list_key" schema option must be a string!');
        }

        // formInspector.locale is nullable, the container always needs one; fall back like the other fields do.
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        return (
            <ProductFamilyAttributes
                dataPath={dataPath}
                disabled={!!disabled}
                formInspector={formInspector}
                listKey={listKey}
                locale={locale}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                router={router}
                schemaPath={schemaPath}
                value={value}
            />
        );
    }
}
