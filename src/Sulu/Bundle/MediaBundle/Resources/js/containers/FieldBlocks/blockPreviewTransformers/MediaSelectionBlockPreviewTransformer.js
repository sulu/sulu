// @flow
import React from 'react';
import {action, isArrayLike, observable} from 'mobx';
import {MimeTypeIndicator} from '../../../components';
import mediaSelectionBlockPreviewTransformerStyles from './mediaSelectionBlockPreviewTransformer.scss';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';

const MAX_LENGTH = 8;

export default class MediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;
    @observable failedImages: Object;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
        this.failedImages = {};
    }

    @action checkForImageError(id: *) {
        fetch(this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50')
            .then((r) => this.failedImages[id] = r.status !== 200);
    }

    @action transform(value: *): Node {
        const {ids} = value;

        if ((!isArrayLike(ids)) || ids.length === 0) {
            return null;
        }

        ids.forEach((id) => this.checkForImageError(id));

        return (
            ids.slice(0, MAX_LENGTH).map((id) => (
                this.failedImages[id] ?
                    <div className={mediaSelectionBlockPreviewTransformerStyles.mimeTypeIndicator} key={id}>
                        <MimeTypeIndicator
                            height={25}
                            iconSize={16}
                            key={id}
                            mimeType="application/pdf"
                            width={25}
                        />
                    </div> :
                    <img
                        className={mediaSelectionBlockPreviewTransformerStyles.image}
                        key={id}
                        src={this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50'}
                    />
            ))
        );
    }
}
