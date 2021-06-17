// @flow
import React from 'react';
import {isArrayLike} from 'mobx';
import mediaSelectionBlockPreviewTransformerStyles from './mediaSelectionBlockPreviewTransformer.scss';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';

const MAX_LENGTH = 8;

export default class MediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
    }

    transform(value: *): Node {
        const {ids} = value;

        if ((!isArrayLike(ids)) || ids.length === 0) {
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
