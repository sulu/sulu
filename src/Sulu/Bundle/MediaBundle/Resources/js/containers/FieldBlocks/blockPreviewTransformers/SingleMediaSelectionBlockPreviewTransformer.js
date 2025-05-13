// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {MimeTypeIndicator} from '../../../components';
import singleMediaSelectionBlockPreviewTransformerStyles from './singleMediaSelectionBlockPreviewTransformer.scss';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from 'sulu-admin-bundle/types';

export default class SingleMediaSelectionBlockPreviewTransformer implements BlockPreviewTransformer {
    imageFormatUrl: string;
    @observable validImage: boolean;

    constructor(imageFormatUrl: string) {
        this.imageFormatUrl = imageFormatUrl;
        this.validImage = true;
    }

    @action checkForImageError(id: *) {
        fetch(this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50')
            .then((r) => this.validImage = r.status === 200);
    }

    @action transform(value: *): Node {
        const {id} = value;

        if (!id) {
            return null;
        }

        this.checkForImageError(id);

        return (
            this.validImage ?
                <img
                    className={singleMediaSelectionBlockPreviewTransformerStyles.image}
                    key={id}
                    src={this.imageFormatUrl.replace(':id', id) + '?locale=en&format=sulu-50x50'}
                /> :
                <MimeTypeIndicator
                    height={25}
                    iconSize={16}
                    key={id}
                    mimeType="application/pdf"
                    width={25}
                />
        );
    }
}
