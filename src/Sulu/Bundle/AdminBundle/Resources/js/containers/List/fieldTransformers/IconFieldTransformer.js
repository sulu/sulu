// @flow
import React from 'react';
import type {Node} from 'react';
import log from 'loglevel';
import classNames from 'classnames';
import Icon from '../../../components/Icon';
import type {FieldTransformer} from '../types';
import iconFieldTransformerStyles from './iconFieldTransformer.scss';

export type Skin = 'default' | 'dark';

export default class IconFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: { [string]: any }): Node {
        if (!value) {
            return value;
        }

        const {
            mapping,
            default: defaultIcon,
            skin = 'default',
        }: {
            default: Object | string,
            mapping: mixed[],
            skin: Skin,
        } = parameters;

        if (!mapping) {
            return value;
        }

        if (typeof mapping !== 'object') {
            log.error('Transformer parameter "mapping" needs to be of type collection.');

            return null;
        }

        let iconConfig = mapping[value];
        if (!iconConfig) {
            if (!defaultIcon) {
                log.warn(
                    `There was no icon specified in the "mapping" transformer parameter for the value "${value}".`
                );

                return value;
            }

            if (typeof defaultIcon !== 'string' && typeof defaultIcon !== 'object') {
                log.warn(
                    'Transformer parameter "default" needs to be of type string or collection, ' +
                    `${typeof defaultIcon} given.`
                );

                return value;
            }

            iconConfig = defaultIcon;
        }

        if (skin && typeof skin !== 'string') {
            log.error(`Transformer parameter "skin" needs to be of type string, ${typeof skin} given.`);

            return null;
        }

        if (typeof iconConfig === 'object') {
            return this.transformObjectConfig(value, iconConfig, skin);
        }

        if (typeof iconConfig === 'string') {
            return this.transformStringConfig(iconConfig, skin);
        }

        log.error(`Transformer parameter "mapping/${value}" needs to be either of type string or collection.`);

        return null;
    }

    transformObjectConfig(value: *, iconConfig: Object, skin: Skin): Node {
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

        return (
            <Icon className={this.getClassName(skin)} name={icon} style={style} />
        );
    }

    transformStringConfig(iconConfig: string, skin: Skin): Node {
        return (
            <Icon className={this.getClassName(skin)} name={iconConfig} />
        );
    }

    getClassName(skin: Skin): Object {
        return classNames(
            iconFieldTransformerStyles.listIcon,
            iconFieldTransformerStyles[skin]
        );
    }
}
