// @flow
import AbstractFormToolbarAction from '../toolbarActions/AbstractFormToolbarAction';

class FormToolbarActionRegistry {
    toolbarActions: {[name: string]: Class<AbstractFormToolbarAction>} = {};

    constructor() {
        this.clear();
    }

    clear() {
        this.toolbarActions = {};
    }

    add(name: string, item: Class<AbstractFormToolbarAction>) {
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

export default new FormToolbarActionRegistry();
