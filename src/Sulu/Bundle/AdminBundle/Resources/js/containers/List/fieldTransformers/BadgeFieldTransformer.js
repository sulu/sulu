// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../../utils';
import badgeFieldTransformerStyles from './badgeFieldTransformer.scss';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

/**
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class BadgeFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: {[string]: any}): Node {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        const {error, prefix = '', success} = parameters;
        const className = classNames(badgeFieldTransformerStyles.badge, {
            [badgeFieldTransformerStyles.success]: value === success,
            [badgeFieldTransformerStyles.error]: value === error,
        });

        return <span className={className}>{translate(prefix + value)}</span>;
    }
}
