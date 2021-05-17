// @flow
import React from 'react';
import {translate} from '../../../utils/Translator';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from '../types';

export default class SmartContentBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        return (
            <p>
                <em>
                    {translate(
                        'sulu_admin.smart_content_block_preview',
                        {limit: value.limitResult ? value.limitResult : 'undefined'}
                    )}
                </em>
            </p>
        );
    }
}
