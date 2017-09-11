// @flow
import type {ComponentType} from 'react';
import Router from '../../services/Router';

export type ViewProps = {
    router: Router,
};

export type View = ComponentType<ViewProps>;
