// @flow
import React from 'react';
import {action, isArrayLike, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {MimeTypeIndicator} from '../../../components';
import mediaSelectionBlockPreviewTransformerStyles from './mediaSelectionBlockPreviewTransformer.scss';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';

const MAX_LENGTH = 8;

export default class MediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;
    requestedImages: Object;
    requestedMedia: Object;
    @observable failedImages: Object;
    @observable media: Object;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
        this.requestedImages = {};
        this.requestedMedia = {};
        this.failedImages = {};
        this.media = {};
    }

    @action checkForImageError(id: *) {
        if (this.requestedImages[id] || this.failedImages[id] !== undefined) {
            return;
        }

        this.requestedImages[id] = true;

        fetch(this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50')
            .then(action((response) => {
                this.failedImages[id] = response.status !== 200;

                if (response.status !== 200) {
                    this.loadMedia(id);
                }
            }))
            .catch(action(() => {
                this.failedImages[id] = true;
                this.loadMedia(id);
            }));
    }

    loadMedia(id: *) {
        if (this.requestedMedia[id] || this.media[id]) {
            return;
        }

        this.requestedMedia[id] = true;

        ResourceRequester.get('media', {id, locale: 'en'})
            .then(action((media) => {
                this.media[id] = media;
            }))
            .catch(() => undefined);
    }

    @action transform(value: *): Node {
        const {ids} = value;

        if ((!isArrayLike(ids)) || ids.length === 0) {
            return null;
        }

        ids.slice(0, MAX_LENGTH).forEach((id) => this.checkForImageError(id));

        return (
            ids.slice(0, MAX_LENGTH).map((id) => (
                this.failedImages[id] && this.media[id] ?
                    <div className={mediaSelectionBlockPreviewTransformerStyles.mimeTypeIndicator} key={id}>
                        <MimeTypeIndicator
                            height={25}
                            iconSize={16}
                            mimeType={this.media[id].mimeType}
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
