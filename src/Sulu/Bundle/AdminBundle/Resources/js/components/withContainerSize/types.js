// @flow
import type {Element} from 'react';

export type WithContainerSizeElement = Element<*> & {containerDidMount?: () => {}};
