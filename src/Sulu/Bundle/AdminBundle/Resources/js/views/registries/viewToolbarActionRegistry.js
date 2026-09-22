// @flow
import AbstractViewToolbarAction from '../toolbarActions/AbstractViewToolbarAction';

class ViewToolbarActionRegistry {
    toolbarActions: {[name: string]: Class<AbstractViewToolbarAction>} = {};

    constructor() {
        this.clear();
    }

    clear() {
        this.toolbarActions = {};
    }

    add(name: string, item: Class<AbstractViewToolbarAction>) {
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

export default new ViewToolbarActionRegistry();
