// @flow
import React from 'react';
import {observable, toJS} from 'mobx';
import userStore from '../../../stores/userStore';
import LinkContainer from '../../Link/Link';
import type {FieldTypeProps} from '../types';
import type {LinkTypeValue} from '../../Link/types';

export default class Link extends React.Component<FieldTypeProps<LinkTypeValue>> {
    render() {
        const {
            disabled,
            formInspector,
            onChange,
            onFinish,
            value,
            schemaOptions: {
                anchor: {
                    value: enableAnchor,
                } = {},
                target: {
                    value: enableTarget,
                } = {},
                types: {
                    value: providerTypes,
                } = {},
            },
        } = this.props;

        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
        if (providerTypes !== undefined && providerTypes !== null && typeof providerTypes !== 'string') {
            throw new Error('The "types" schema option must be a string if given!');
        }
        const types = providerTypes ? providerTypes.split(',').map((name) => name.trim()) : [];

        if (enableAnchor !== undefined && enableAnchor !== null && typeof enableAnchor !== 'boolean') {
            throw new Error('The "anchor" schema option must be a boolean if given!');
        }

        if (enableTarget !== undefined && enableTarget !== null && typeof enableTarget !== 'boolean') {
            throw new Error('The "target" schema option must be a boolean if given!');
        }

        return (
            <LinkContainer
                disabled={disabled === true}
                enableAnchor={enableAnchor}
                enableTarget={enableTarget}
                locale={toJS(locale)}
                onChange={onChange}
                onFinish={onFinish}
                types={types}
                value={value}
            />
        );
    }
}
