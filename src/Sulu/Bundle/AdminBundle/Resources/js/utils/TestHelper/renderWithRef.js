// @flow
import React from 'react';
import {render} from '@testing-library/react';
import type {Element} from 'react';

type RenderWithRefOptions = {|
    afterRender?: (instance: any) => void,
|};

function createTestRef(): any {
    return (React.createRef(): any);
}

function renderWithRef(element: Element<*>, options?: RenderWithRefOptions): any {
    const ref = createTestRef();
    const utils = render(React.cloneElement(element, {ref}));
    const instance = ref.current;

    if (instance && options && options.afterRender) {
        options.afterRender(instance);
    }

    return {
        instance,
        ref,
        ...utils,
        rerender: (nextElement: Element<*>) => utils.rerender(React.cloneElement(nextElement, {ref})),
    };
}

export {createTestRef};
export default renderWithRef;
