// @flow
import React from 'react';
import ProductAttributes from '../../Product/ProductAttributes';
import type {FieldTypeProps} from '../../../types';

type Props = FieldTypeProps<?{[string]: mixed}>;

/**
 * @experimental We can not yet give BC Promise for this new field type in Sulu 3.1.
 */
export default class ProductAttributesField extends React.Component<Props> {
    handleChange = (value: {[string]: mixed}) => {
        this.props.onChange(value);
    };

    handleFinish = (rowDataPath: string, rowSchemaPath: string) => {
        this.props.onFinish(rowDataPath, rowSchemaPath);
    };

    render() {
        const {
            dataPath,
            disabled,
            error,
            formInspector,
            router,
            schemaPath,
            schemaOptions: {
                variant: {
                    value: variant,
                } = {},
            } = {},
            showAllErrors,
            value,
        } = this.props;

        if (variant !== undefined && typeof variant !== 'boolean') {
            throw new Error('The "variant" schema option must be a boolean if given!');
        }

        return (
            <ProductAttributes
                dataPath={dataPath}
                disabled={!!disabled}
                error={error}
                formInspector={formInspector}
                onChange={this.handleChange}
                onFinish={this.handleFinish}
                router={router}
                schemaPath={schemaPath}
                showAllErrors={showAllErrors}
                value={value}
                variant={!!variant}
            />
        );
    }
}
