// @flow
import React from 'react';

function matchesElementType(element: any, type: any): boolean {
    if (element.type === type) {
        return true;
    }

    if (typeof type === 'string') {
        return element.type && (
            element.type.displayName === type || element.type.name === type || element.type === type
        );
    }

    return false;
}

function findElement(element: any, predicate: (element: any) => boolean): any {
    if (Array.isArray(element)) {
        for (const child of element) {
            const match = findElement(child, predicate);

            if (match) {
                return match;
            }
        }

        return undefined;
    }

    if (!React.isValidElement(element)) {
        return undefined;
    }

    if (predicate(element)) {
        return element;
    }

    const children = React.Children.toArray(element.props.children);

    for (const child of children) {
        const match = findElement(child, predicate);

        if (match) {
            return match;
        }
    }

    return undefined;
}

function findAllElements(element: any, predicate: (element: any) => boolean): Array<any> {
    if (Array.isArray(element)) {
        const matches = [];

        for (const child of element) {
            matches.push(...findAllElements(child, predicate));
        }

        return matches;
    }

    if (!React.isValidElement(element)) {
        return [];
    }

    const matches = predicate(element) ? [element] : [];
    const children = React.Children.toArray(element.props.children);

    for (const child of children) {
        matches.push(...findAllElements(child, predicate));
    }

    return matches;
}

function findElementByType(element: any, type: any): any {
    const match = findElement(element, (element) => matchesElementType(element, type));

    if (!match) {
        throw new Error('Element not found');
    }

    return match;
}

function findAllElementsByType(element: any, type: any): Array<any> {
    return findAllElements(element, (element) => matchesElementType(element, type));
}

export {
    findAllElements,
    findAllElementsByType,
    findElement,
    findElementByType,
};
