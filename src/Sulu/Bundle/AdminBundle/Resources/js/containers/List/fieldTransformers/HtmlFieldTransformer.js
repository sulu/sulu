// @flow
import React from 'react';
import sanitizeHtml from 'sanitize-html';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class HtmlFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        const sanitizedHtml = sanitizeHtml(value.toString(), {
            allowedTags: ['b', 'em', 'i', 's', 'small', 'strong', 'sub', 'sup', 'time', 'u'],
            allowedAttributes: {},
            disallowedTagsMode: 'recursiveEscape',
        });

        return <div dangerouslySetInnerHTML={{__html: sanitizedHtml}} />;
    }
}
