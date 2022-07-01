// @flow
import React from 'react';
import {isArrayLike, observable} from 'mobx';
import log from 'loglevel';
import userStore from '../../../stores/userStore';
import LinkContainer from '../../Link/Link';
import type {FieldTypeProps, SchemaOption} from '../types';
import type {LinkValue} from '../../Link/types';
import type {IObservableArray} from 'mobx/lib/mobx';

export default class Link extends React.Component<FieldTypeProps<LinkValue>> {
    render() {
        const {
            disabled,
            formInspector,
            onChange,
            onFinish,
            value,
            schemaOptions: {
                enable_anchor: {
                    value: deprecatedEnableAnchor,
                } = {},
                enable_target: {
                    value: deprecatedEnableTarget,
                } = {},
                enable_title: {
                    value: deprecatedEnableTitle,
                } = {},
                enable_attributes: {
                    value: enableAttributes,
                } = {},
                types: {
                    value: unvalidatedTypes,
                } = {},
                excluded_types: {
                    value: unvalidatedExcludedTypes,
                } = {},
            },
        } = this.props;

        const enabledAttributes = [];

        if (enableAttributes !== undefined) {
            if (!isArrayLike(enableAttributes)) {
                throw new Error('The "enable_attributes" schema option must be an array!');
            }

            for (const attr of ((enableAttributes: any): Iterable<SchemaOption>)) {
                if (attr.value !== undefined && attr.value !== null) {
                    throw new Error(`The "enable_attributes.${attr.name}" schema option must not have a value!`);
                }

                enabledAttributes.push(attr.name);
            }
        } else {
            if (deprecatedEnableAnchor !== undefined) {
                log.warn(
                    'The "enable_anchor" schema option is deprecated since version 2.5 and will be removed. ' +
                    'Use the "enable_attributes" option instead.'
                );

                enabledAttributes.push('anchor');
            }

            if (deprecatedEnableTarget !== undefined) {
                log.warn(
                    'The "enable_target" schema option is deprecated since version 2.5 and will be removed. ' +
                    'Use the "enable_attributes" option instead.'
                );

                enabledAttributes.push('target');
            }

            if (deprecatedEnableTitle !== undefined) {
                log.warn(
                    'The "enable_title" schema option is deprecated since version 2.5 and will be removed. ' +
                    'Use the "enable_attributes" option instead.'
                );

                enabledAttributes.push('title');
            }
        }

        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        let providerTypes;

        if (unvalidatedTypes) {
            if (!isArrayLike(unvalidatedTypes)) {
                throw new Error('The "types" schema option must be an array!');
            }
            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
            const types: Array<any> | IObservableArray<any> = unvalidatedTypes;

            if (types.length === 0) {
                throw new Error('The "types" schema option must contain some values!');
            }

            providerTypes = types.map((type) => {
                if (typeof type.name !== 'string') {
                    throw new Error(
                        'Every type in the "types" schemaOption must contain a string as name'
                    );
                }

                return type.name;
            });
        }

        let excludedProviderTypes = [];

        if (unvalidatedExcludedTypes) {
            if (!isArrayLike(unvalidatedExcludedTypes)) {
                throw new Error('The "excluded_types" schema option must be an array!');
            }
            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
            const excludedTypes: Array<any> | IObservableArray<any> = unvalidatedExcludedTypes;

            if (excludedTypes.length === 0) {
                throw new Error('The "excluded_types" schema option must contain some values!');
            }

            excludedProviderTypes = excludedTypes.map((type) => {
                if (typeof type.name !== 'string') {
                    throw new Error(
                        'Every type in the "excluded_types" schemaOption must contain a string as name'
                    );
                }
                return type.name;
            });
        }

        return (
            <LinkContainer
                disabled={!!disabled}
                enableAnchor={enabledAttributes.includes('anchor')}
                enableRel={enabledAttributes.includes('rel')}
                enableTarget={enabledAttributes.includes('target')}
                enableTitle={enabledAttributes.includes('title')}
                excludedTypes={excludedProviderTypes}
                locale={locale}
                onChange={onChange}
                onFinish={onFinish}
                types={providerTypes}
                value={value}
            />
        );
    }
}
