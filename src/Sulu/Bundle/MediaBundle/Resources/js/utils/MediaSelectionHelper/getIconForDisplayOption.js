// @flow
import type {DisplayOption} from '../../types';

export default function getIconForDisplayOption(value: ?DisplayOption) {
    switch (value) {
        case 'leftTop':
            return 'su-display-top-left';
        case 'top':
            return 'su-display-top-center';
        case 'rightTop':
            return 'su-display-top-right';
        case 'left':
            return 'su-display-center-left';
        case 'middle':
            return 'su-display-center-center';
        case 'right':
            return 'su-display-center-right';
        case 'leftBottom':
            return 'su-display-bottom-left';
        case 'bottom':
            return 'su-display-bottom-center';
        case 'rightBottom':
            return 'su-display-bottom-right';
        default:
            return 'su-display-default';
    }
}
