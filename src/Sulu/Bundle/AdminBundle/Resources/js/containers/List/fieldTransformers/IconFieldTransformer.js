// @flow
import React from 'react';
import type {Node} from 'react';
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
            throw new Error('Parameter "icons" needs to be of type collection!');
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
                    throw new Error(`Parameter "icons/${value}/icon" needs to be set!`);
                }

                if (typeof iconConfig.icon !== 'string') {
                    throw new Error(`Parameter "icons/${value}/icon" needs to be of type string!`);
                }

                icon = iconConfig.icon;

                if (iconConfig.color) {
                    if (typeof iconConfig.color !== 'string') {
                        throw new Error(`Parameter "icons/${value}/color" needs to be of type string!`);
                    }

                    color = iconConfig.color;
                }

                break;
            case 'string':
                icon = iconConfig;

                break;
            default:
                throw new Error(`Parameter "icons/${value}" needs to be either of type string or collection!`);
        }

        return <Icon className={iconFieldTransformerStyles.listIcon} name={icon} style={{color}} />;
    }
}
