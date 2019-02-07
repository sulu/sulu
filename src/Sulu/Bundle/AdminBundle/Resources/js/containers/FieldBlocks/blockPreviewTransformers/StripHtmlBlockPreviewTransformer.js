// @flow
import React from 'react';
import type {Node} from 'react';
import textVersion from 'textversionjs';
import type {BlockPreviewTransformer} from '../types';

export default class StripHtmlBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (typeof value !== 'string') {
            return null;
        }

        return (
            <p>
                {textVersion(value, {headingStyle: 'linebreak', listStyle: 'linebreak'})}
            </p>
        );
    }
}
