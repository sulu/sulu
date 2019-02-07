// @flow
import React from 'react';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';
import singleMediaSelectionBlockPreviewTransformerStyles from './singleMediaSelectionBlockPreviewTransformer.scss';

export default class SingleMediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
    }

    transform(value: *): Node {
        const {id} = value;

        if (!id) {
            return null;
        }

        return (
            <img
                className={singleMediaSelectionBlockPreviewTransformerStyles.image}
                key={id}
                src={this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50'}
            />
        );
    }
}
