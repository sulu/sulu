// @flow
import AbstractListItemAction from '../itemActions/AbstractListItemAction';

class ListItemActionRegistry {
    listItemActions: {[name: string]: Class<AbstractListItemAction>} = {};

    constructor() {
        this.clear();
    }

    clear() {
        this.listItemActions = {};
    }

    add(name: string, item: Class<AbstractListItemAction>) {
        if (name in this.listItemActions) {
            throw new Error('The key "' + name + '" has already been used for another ItemAction!');
        }

        this.listItemActions[name] = item;
    }

    get(name: string) {
        if (!(name in this.listItemActions)) {
            throw new Error(
                'There is no ItemAction with key "' + name + '" registered!' +
                '\n\nRegistered keys: ' + Object.keys(this.listItemActions).sort().join(', ')
            );
        }

        return this.listItemActions[name];
    }
}

export default new ListItemActionRegistry();
