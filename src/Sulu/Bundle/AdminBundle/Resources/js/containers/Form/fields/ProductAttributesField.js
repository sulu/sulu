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

    handleFinish = () => {
        this.props.onFinish();
    };

    render() {
        const {
            dataPath,
            disabled,
            formInspector,
            router,
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
                formInspector={formInspector}
                onChange={this.handleChange}
                onFinish={this.handleFinish}
                router={router}
                showAllErrors={showAllErrors}
                value={value}
                variant={!!variant}
            />
        );
    }
}
