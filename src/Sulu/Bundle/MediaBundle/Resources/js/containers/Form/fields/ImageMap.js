// @flow
import React from 'react';
import {observable} from 'mobx';
import debounce from 'debounce';
import userStore from 'sulu-admin-bundle/stores/userStore';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import ImageMapContainer from '../../ImageMap';
import type {Value as ImageMapValue} from '../../ImageMap/types';

export default class ImageMap extends React.Component<FieldTypeProps<ImageMapValue>> {
    handleFinish = debounce(() => {
        const {onFinish} = this.props;

        onFinish();
    }, 1000);

    handleChange = (value: ImageMapValue) => {
        const {onChange} = this.props;

        onChange(value);
        this.handleFinish();
    };

    get defaultValue(): ImageMapValue {
        return {
            imageId: undefined,
            hotspots: [],
        };
    }

    render() {
        const {
            error,
            disabled,
            formInspector,
            value,
        } = this.props;

        const locale = formInspector.locale
            ? formInspector.locale
            : observable.box(userStore.contentLocale);

        return (
            <ImageMapContainer
                disabled={!!disabled}
                formTypes={['default']}
                locale={locale}
                onChange={this.handleChange}
                valid={!error}
                value={value || this.defaultValue}
            />
        );
    }
}
