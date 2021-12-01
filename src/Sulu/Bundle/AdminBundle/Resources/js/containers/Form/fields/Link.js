// @flow
import React from 'react';
import {isArrayLike, observable} from 'mobx';
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
                enable_target: {
                    value: enableTarget,
                } = {},
                enable_title: {
                    value: enableTitle,
                } = {},
                types: {
                    value: unvalidatedTypes,
                } = {},
            },
        } = this.props;

        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        let providerTypes = undefined;

        if (unvalidatedTypes) {
            if (!isArrayLike(unvalidatedTypes)) {
                throw new Error('The "schemes" schema option must be an array!');
            }
            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
            const types: Array<any> | IObservableArray<any> = unvalidatedTypes;

            if (types.length === 0) {
                throw new Error('The "schemes" schema option must contain some values!');
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

        if (enableAnchor !== undefined && enableAnchor !== null && typeof enableAnchor !== 'boolean') {
            throw new Error('The "anchor" schema option must be a boolean if given!');
        }

        if (enableTarget !== undefined && enableTarget !== null && typeof enableTarget !== 'boolean') {
            throw new Error('The "target" schema option must be a boolean if given!');
        }

        if (enableTitle !== undefined && enableTitle !== null && typeof enableTitle !== 'boolean') {
            throw new Error('The "title" schema option must be a boolean if given!');
        }

        return (
            <LinkContainer
                disabled={!!disabled}
                enableAnchor={enableAnchor}
                enableTarget={enableTarget}
                enableTitle={enableTitle}
                locale={locale}
                onChange={onChange}
                onFinish={onFinish}
                types={providerTypes}
                value={value}
            />
        );
    }
}
