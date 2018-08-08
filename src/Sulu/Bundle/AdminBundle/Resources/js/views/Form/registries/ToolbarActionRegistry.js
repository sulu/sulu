// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';

class ToolbarActionRegistry {
    toolbarActions: {[name: string]: Class<AbstractToolbarAction>} = {};

    constructor() {
        this.clear();
    }

    clear() {
        this.toolbarActions = {};
    }

    add(name: string, item: Class<AbstractToolbarAction>) {
        if (name in this.toolbarActions) {
            throw new Error('The key "' + name + '" has already been used for another ToolbarAction!');
        }

        this.toolbarActions[name] = item;
    }

    get(name: string) {
        if (!(name in this.toolbarActions)) {
            throw new Error('There is no toolbar item with key "' + name + '" registered!');
        }

        return this.toolbarActions[name];
    }
}

export default new ToolbarActionRegistry();
