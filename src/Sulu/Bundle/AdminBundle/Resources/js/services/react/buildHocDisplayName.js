// @flow
import type {ElementType} from 'react';

function getComponentName(Component: ElementType): string {
    if (typeof Component.displayName === 'string') {
        return Component.displayName;
    }

    if (typeof Component.name === 'string') {
        return Component.name;
    }

    return '';
}

export default function buildHocDisplayName(hocName: string, Component: ElementType): string {
    return `${hocName}(${getComponentName(Component)})`;
}
