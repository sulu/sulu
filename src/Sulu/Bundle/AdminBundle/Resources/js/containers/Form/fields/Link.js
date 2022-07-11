// @flow
import React from 'react';
import {isArrayLike, observable} from 'mobx';
import log from 'loglevel';
import userStore from '../../../stores/userStore';
import LinkContainer from '../../Link/Link';
import type {FieldTypeProps} from '../types';
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
                    value: enableAnchor,
                } = {},
                enable_query: {
                    value: enableQuery,
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

        if (enableAnchor !== undefined && enableAnchor !== null && typeof enableAnchor !== 'boolean') {
            throw new Error('The "enable_anchor" schema option must be a boolean if given!');
        }

        if (enableQuery !== undefined && enableQuery !== null && typeof enableQuery !== 'boolean') {
            throw new Error('The "enable_query" schema option must be a boolean if given!');
        }

        let enableTarget = false,
            enableTitle = false,
            enableRel = false;

        if (enableAttributes !== undefined && enableAttributes !== null) {
            if (typeof enableAttributes !== 'boolean') {
                throw new Error('The "enable_attributes" schema option must be a boolean!');
            }

            enableTarget = enableAttributes;
            enableTitle = enableAttributes;
            enableRel = enableAttributes;
        } else {
            if (deprecatedEnableTarget !== undefined && deprecatedEnableTarget !== null) {
                log.warn(
                    'The "enable_target" schema option is deprecated since version 2.5 and will be removed. ' +
                    'Use the "enable_attributes" option instead.'
                );

                if (typeof deprecatedEnableTarget !== 'boolean') {
                    throw new Error('The "enable_target" schema option must be a boolean!');
                }

                enableTarget = deprecatedEnableTarget;
            }

            if (deprecatedEnableTitle !== undefined && deprecatedEnableTitle !== null) {
                log.warn(
                    'The "enable_title" schema option is deprecated since version 2.5 and will be removed. ' +
                    'Use the "enable_attributes" option instead.'
                );

                if (typeof deprecatedEnableTitle !== 'boolean') {
                    throw new Error('The "enable_title" schema option must be a boolean!');
                }

                enableTitle = deprecatedEnableTitle;
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
                enableAnchor={enableAnchor}
                enableQuery={enableQuery}
                enableRel={enableRel}
                enableTarget={enableTarget}
                enableTitle={enableTitle}
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
