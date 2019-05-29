// @flow
import {translate} from 'sulu-admin-bundle/utils';
import type {DisplayOption} from '../../types';

export default function getTranslationForDisplayOption(value: ?DisplayOption) {
    switch (value) {
        case 'leftTop':
            return translate('sulu_media.left_top');
        case 'top':
            return translate('sulu_media.top');
        case 'rightTop':
            return translate('sulu_media.right_top');
        case 'left':
            return translate('sulu_media.left');
        case 'middle':
            return translate('sulu_media.middle');
        case 'right':
            return translate('sulu_media.right');
        case 'leftBottom':
            return translate('sulu_media.left_bottom');
        case 'bottom':
            return translate('sulu_media.bottom');
        case 'rightBottom':
            return translate('sulu_media.right_bottom');
        default:
            return '';
    }
}
