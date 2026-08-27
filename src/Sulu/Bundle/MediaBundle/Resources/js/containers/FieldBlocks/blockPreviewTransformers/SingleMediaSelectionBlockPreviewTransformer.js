// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {MimeTypeIndicator} from '../../../components';
import singleMediaSelectionBlockPreviewTransformerStyles from './singleMediaSelectionBlockPreviewTransformer.scss';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';

export default class SingleMediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;
    requestedImages: Object;
    requestedMedia: Object;
    @observable imageAvailable: Object;
    @observable media: Object;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
        this.requestedImages = {};
        this.requestedMedia = {};
        this.imageAvailable = {};
        this.media = {};
    }

    @action checkForImageError(id: *) {
        if (this.requestedImages[id] || this.imageAvailable[id] !== undefined) {
            return;
        }

        this.requestedImages[id] = true;

        fetch(this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50')
            .then(action((response) => {
                this.imageAvailable[id] = response.status === 200;

                if (response.status !== 200) {
                    this.loadMedia(id);
                }
            }))
            .catch(action(() => {
                this.imageAvailable[id] = false;
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
        const {id} = value;

        if (!id) {
            return null;
        }

        this.checkForImageError(id);

        return (
            this.imageAvailable[id] !== false ?
                <img
                    className={singleMediaSelectionBlockPreviewTransformerStyles.image}
                    key={id}
                    src={this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50'}
                /> :
                this.media[id]
                    ? <MimeTypeIndicator
                        height={25}
                        iconSize={16}
                        key={id}
                        mimeType={this.media[id].mimeType}
                        width={25}
                    />
                    : null
        );
    }
}
