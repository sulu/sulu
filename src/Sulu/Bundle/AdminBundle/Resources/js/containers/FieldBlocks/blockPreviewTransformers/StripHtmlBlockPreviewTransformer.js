// @flow
import React from 'react';
import type {Node} from 'react';
import textVersion from 'textversionjs';
import type {BlockPreviewTransformer} from '../types';

const MAX_LENGTH = 50;

export default class StripHtmlBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (typeof value !== 'string') {
            return null;
        }

        const text = textVersion(value, {headingStyle: 'linebreak', listStyle: 'linebreak'});

        return (
            <p>
                {text.length > MAX_LENGTH ? text.substring(0, MAX_LENGTH) + '...' : text}
            </p>
        );
    }
}
