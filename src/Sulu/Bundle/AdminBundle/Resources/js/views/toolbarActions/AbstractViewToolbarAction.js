// @flow
import Router from '../../services/Router';
import type {ToolbarItemConfig} from '../../containers/Toolbar/types';
import type {Node} from 'react';

export default class AbstractViewToolbarAction {
    router: Router;
    options: {[key: string]: mixed};

    constructor(router: Router, options: {[key: string]: mixed} = {}) {
        this.router = router;
        this.options = options;
    }

    getNode(): Node {
        return null;
    }

    getToolbarItemConfig(): ?ToolbarItemConfig<*> {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }

    destroy() {

    }
}
