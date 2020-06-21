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
            log.warn(`Transformer parameter "mapping/${value}" is not set.`);

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
        if (!iconConfig.icon) {
            log.error(`Transformer parameter "mapping/${value}/icon" needs to be set.`);

            return null;
        }

        if (typeof iconConfig.icon !== 'string') {
            log.error(`Transformer parameter "mapping/${value}/icon" needs to be of type string.`);

            return null;
        }

        const icon = iconConfig.icon;

        if (iconConfig.color) {
            if (typeof iconConfig.color !== 'string') {
                log.error(`Transformer parameter "mapping/${value}/color" needs to be of type string.`);

                return null;
            }

            return (
                <Icon className={iconFieldTransformerStyles.listIcon} name={icon} style={{color: iconConfig.color}} />
            );
        }

        return <Icon className={iconFieldTransformerStyles.listIcon} name={icon} />;
    }

    transformStringConfig(iconConfig: Object): Node {
        return <Icon className={iconFieldTransformerStyles.listIcon} name={iconConfig} />;
    }
}
