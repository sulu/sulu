// @flow
import React from 'react';
import type {Node} from 'react';
import {isObservableArray} from 'mobx';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';
import mediaSelectionBlockPreviewTransformerStyles from './mediaSelectionBlockPreviewTransformer.scss';

const MAX_LENGTH = 8;

export default class MediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
    }

    transform(value: *): Node {
        const {ids} = value;

        if ((!Array.isArray(ids) && !isObservableArray(ids)) || ids.length === 0) {
            return null;
        }

        return (
            <div>
                {ids.slice(0, MAX_LENGTH).map((id) => (
                    <img
                        className={mediaSelectionBlockPreviewTransformerStyles.image}
                        key={id}
                        src={this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50'}
                    />
                ))}
            </div>
        );
    }
}
