// @flow
import type {ElementType} from 'react';

function getComponentName(Component: ElementType): string {
    if ('string' === typeof Component.displayName) {
        return Component.displayName;
    }

    if ('string' === typeof Component.name) {
        return Component.name;
    }

    return '';
}

export default function buildHocDisplayName(hocName: string, Component: ElementType): string {
    return `${hocName}(${getComponentName(Component)})`;
}
