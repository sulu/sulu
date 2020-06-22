// @flow
import React from 'react';
import type {Node} from 'react';
import log from 'loglevel';
import Icon from '../../../components/Icon';
import type {FieldTransformer} from '../types';
import iconFieldTransformerStyles from './iconFieldTransformer.scss';

export default class IconFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: {[string]: mixed}): Node {
        if (!value) {
            return value;
        }

        const {mapping} = parameters;
        if (!mapping) {
            return value;
        }

        if (typeof mapping !== 'object') {
            log.error('Transformer parameter "mapping" needs to be of type collection.');

            return null;
        }

        const iconConfig = mapping[value];
        if (!iconConfig) {
            log.warn(`There was no icon specified in the "mapping" transformer parameter for the value "${value}".`);

            return value;
        }

        if (typeof iconConfig === 'object') {
            return this.transformObjectConfig(value, iconConfig);
        }

        if (typeof iconConfig === 'string') {
            return this.transformStringConfig(iconConfig);
        }

        log.error(`Transformer parameter "mapping/${value}" needs to be either of type string or collection.`);

        return null;
    }

    transformObjectConfig(value: *, iconConfig: Object): Node {
        const {icon, color} = iconConfig;

        if (!icon || typeof icon !== 'string') {
            log.error(`Transformer parameter "mapping/${value}/icon" needs to be of type string.`);

            return null;
        }

        if (color !== undefined && typeof color !== 'string') {
            log.error(`Transformer parameter "mapping/${value}/color" needs to be of type string.`);

            return null;
        }

        const style = {};

        if (color) {
            style.color = color;
        }

        return <Icon className={iconFieldTransformerStyles.listIcon} name={icon} style={style} />;
    }

    transformStringConfig(iconConfig: Object): Node {
        return <Icon className={iconFieldTransformerStyles.listIcon} name={iconConfig} />;
    }
}
