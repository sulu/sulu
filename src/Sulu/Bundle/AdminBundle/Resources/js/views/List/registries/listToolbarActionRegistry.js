// @flow
import AbstractListToolbarAction from '../toolbarActions/AbstractListToolbarAction';

class ListToolbarActionRegistry {
    toolbarActions: {[name: string]: Class<AbstractListToolbarAction>} = {};

    constructor() {
        this.clear();
    }

    clear() {
        this.toolbarActions = {};
    }

    add(name: string, item: Class<AbstractListToolbarAction>) {
        if (name in this.toolbarActions) {
            throw new Error('The key "' + name + '" has already been used for another ToolbarAction!');
        }

        this.toolbarActions[name] = item;
    }

    get(name: string) {
        if (!(name in this.toolbarActions)) {
            throw new Error(
                'There is no toolbar item with key "' + name + '" registered!' +
                '\n\nRegistered keys: ' + Object.keys(this.toolbarActions).sort().join(', ')
            );
        }

        return this.toolbarActions[name];
    }
}

export default new ListToolbarActionRegistry();
