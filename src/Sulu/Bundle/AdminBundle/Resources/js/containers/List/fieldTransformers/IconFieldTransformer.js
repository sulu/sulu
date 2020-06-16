// @flow
import React from 'react';
import type {Node} from 'react';
import log from 'loglevel';
import type {FieldTransformer} from '../types';
import Icon from '../../../components/Icon';
import iconFieldTransformerStyles from './iconFieldTransformer.scss';

export default class IconFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: ?{[string]: mixed}): Node {
        if (!value || !parameters) {
            return value;
        }

        const {icons} = parameters;
        if (!icons) {
            return value;
        }

        if (typeof icons !== 'object') {
            log.error('Parameter "icons" needs to be of type collection.');

            return null;
        }

        const iconConfig = icons[value];
        if (!iconConfig) {
            return value;
        }

        let icon = null;
        let color = null;
        switch (typeof iconConfig) {
            case 'object':
                if (!iconConfig.icon) {
                    log.error(`Parameter "icons/${value}/icon" needs to be set.`);

                    return null;
                }

                if (typeof iconConfig.icon !== 'string') {
                    log.error(`Parameter "icons/${value}/icon" needs to be of type string.`);

                    return null;
                }

                icon = iconConfig.icon;

                if (iconConfig.color) {
                    if (typeof iconConfig.color !== 'string') {
                        log.error(`Parameter "icons/${value}/color" needs to be of type string.`);

                        return null;
                    }

                    color = iconConfig.color;
                }

                break;
            case 'string':
                icon = iconConfig;

                break;
            default:
                log.error(`Parameter "icons/${value}" needs to be either of type string or collection.`);

                return null;
        }

        return <Icon className={iconFieldTransformerStyles.listIcon} name={icon} style={{color}} />;
    }
}
